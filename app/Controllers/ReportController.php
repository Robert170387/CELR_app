<?php
// app/Controllers/ReportController.php

namespace App\Controllers;

require_once __DIR__ . '/../Core/Controller.php';
require_once __DIR__ . '/../../includes/db.php';

use App\Core\Controller;
use PDO;
use PDOException;

class ReportController extends Controller
{
    /**
     * Retrieves metrics for Vehicle Expenses Dashboard
     * Optimized to use subqueries and assumed indexes
     */
    public function getVehicleExpenseMetrics($vehicleId = null)
    {
        try {
            if ($vehicleId) {
                return $this->getSingleVehicleMetrics($vehicleId);
            } else {
                return $this->getAllVehicleMetrics();
            }
        } catch (PDOException $e) {
            // Log error or rethrow
            return ['error' => $e->getMessage()];
        }
    }

    private function getAllVehicleMetrics()
    {
        // 2026-01-31: Optimization - Use subqueries with expected indexes on expenses(vehicle_id) and trips(vehicle_id)
        $sql = "SELECT v.*, 
                (SELECT COUNT(*) FROM expenses WHERE vehicle_id = v.id) as expense_count,
                ( COALESCE((SELECT SUM(amount) FROM expenses WHERE vehicle_id = v.id), 0) + 
                  COALESCE((SELECT SUM(commission_value) FROM trips WHERE vehicle_id = v.id), 0) ) as total_expenses,
                (SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE vehicle_id = v.id AND paid_by = 'Conductor') as conductor_expenses,
                ( COALESCE((SELECT SUM(amount) FROM expenses WHERE vehicle_id = v.id AND paid_by = 'Propietario'), 0) +
                  COALESCE((SELECT SUM(commission_value) FROM trips WHERE vehicle_id = v.id), 0) ) as owner_expenses,
                (SELECT COALESCE(SUM(kms_total), 0) FROM trips WHERE vehicle_id = v.id) as total_kms
                FROM vehicles v
                WHERE v.active = 1
                ORDER BY total_expenses DESC";
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getSingleVehicleMetrics($vehicleId)
    {
        // Metric 1: Vehicle Details
        $stmt = $this->pdo->prepare("SELECT * FROM vehicles WHERE id = ?");
        $stmt->execute([$vehicleId]);
        $detail = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$detail)
            return null;

        // Metric 2: Monthly Trend (Expenses + Commissions) in one query if possible or union
        $stmtTrend = $this->pdo->prepare("
            SELECT month, month_label, SUM(amount) as total
            FROM (
                SELECT DATE_FORMAT(date, '%Y-%m') as month,
                       DATE_FORMAT(date, '%b %Y') as month_label,
                       amount
                FROM expenses
                WHERE vehicle_id = ? AND date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                
                UNION ALL
                
                SELECT DATE_FORMAT(date_load, '%Y-%m') as month,
                       DATE_FORMAT(date_load, '%b %Y') as month_label,
                       commission_value as amount
                FROM trips
                WHERE vehicle_id = ? AND date_load >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH) AND commission_value > 0
            ) as combined
            GROUP BY month
            ORDER BY month ASC
        ");
        $stmtTrend->execute([$vehicleId, $vehicleId]);
        $trend = $stmtTrend->fetchAll(PDO::FETCH_ASSOC);

        // Metric 3: Category Breakdown
        $stmtCat = $this->pdo->prepare("
            SELECT COALESCE(ec.name, e.category_name) as category_name,
                   COALESCE(ec.slug, e.category) as category,
                   COUNT(*) as count,
                   SUM(e.amount) as total
            FROM expenses e
            LEFT JOIN expense_categories ec ON e.category_id = ec.id
            WHERE e.vehicle_id = ?
            GROUP BY ec.id, e.category_name, e.category
            UNION ALL
            SELECT 'Comisión (Conductor)' as category_name,
                   'comision_viajes' as category,
                   COUNT(*) as count,
                   SUM(commission_value) as total
            FROM trips
            WHERE vehicle_id = ? AND commission_value > 0
            ORDER BY total DESC
        ");
        $stmtCat->execute([$vehicleId, $vehicleId]);
        $categories = $stmtCat->fetchAll(PDO::FETCH_ASSOC);

        // Metric 4: Recent Expenses
        $stmtRecent = $this->pdo->prepare("
            SELECT e.*, COALESCE(ec.name, e.category_name) as category_name
            FROM expenses e
            LEFT JOIN expense_categories ec ON e.category_id = ec.id
            WHERE e.vehicle_id = ?
            ORDER BY e.date DESC
            LIMIT 10
        ");
        $stmtRecent->execute([$vehicleId]);
        $recent = $stmtRecent->fetchAll(PDO::FETCH_ASSOC);

        // Metric 5: Totals Card
        $sqlTotals = "SELECT 
                (SELECT COUNT(*) FROM expenses WHERE vehicle_id = ?) as expense_count,
                ( COALESCE((SELECT SUM(amount) FROM expenses WHERE vehicle_id = ?), 0) + 
                  COALESCE((SELECT SUM(commission_value) FROM trips WHERE vehicle_id = ?), 0) ) as total_expenses,
                (SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE vehicle_id = ? AND paid_by = 'Conductor') as conductor_expenses,
                ( COALESCE((SELECT SUM(amount) FROM expenses WHERE vehicle_id = ? AND paid_by = 'Propietario'), 0) +
                  COALESCE((SELECT SUM(commission_value) FROM trips WHERE vehicle_id = ?), 0) ) as owner_expenses,
                (SELECT COALESCE(SUM(kms_total), 0) FROM trips WHERE vehicle_id = ?) as total_kms";
        $stmtTotals = $this->pdo->prepare($sqlTotals);
        $stmtTotals->execute([$vehicleId, $vehicleId, $vehicleId, $vehicleId, $vehicleId, $vehicleId, $vehicleId]);
        $totals = $stmtTotals->fetch(PDO::FETCH_ASSOC);

        return [
            'detail' => $detail,
            'monthly_trend' => $trend,
            'category_breakdown' => $categories,
            'recent_expenses' => $recent,
            'totals' => $totals
        ];
    }

    /**
     * Retrieves General Financial Report Data
     */
    public function getGeneralFinancialReport($year, $vehicleId = null, $driverId = null)
    {
        // Initialize Data Arrays
        $months = range(1, 12);
        $income = array_fill(1, 12, 0);
        $expenses_by_cat = [];
        $commissions = array_fill(1, 12, 0);

        // 1. Income (Trips)
        $sqlTrips = "SELECT MONTH(date_load) as m, 
                     SUM(flete_neto) as total_income, 
                     SUM(commission_value) as total_comm 
                     FROM trips 
                     WHERE YEAR(date_load) = ?";
        $paramsTrips = [$year];

        if ($vehicleId) {
            $sqlTrips .= " AND vehicle_id = ?";
            $paramsTrips[] = $vehicleId;
        }
        if ($driverId) {
            $sqlTrips .= " AND driver_id = ?";
            $paramsTrips[] = $driverId;
        }
        $sqlTrips .= " GROUP BY m";

        $stmt = $this->pdo->prepare($sqlTrips);
        $stmt->execute($paramsTrips);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $income[$row['m']] = $row['total_income'];
            $commissions[$row['m']] = $row['total_comm'];
        }

        // 2. Expenses
        $sqlExp = "SELECT MONTH(e.date) as m, COALESCE(ec.name, e.category_name, e.category) as category, SUM(e.amount) as total 
                   FROM expenses e 
                   LEFT JOIN trips t ON e.trip_id = t.id 
                   LEFT JOIN expense_categories ec ON e.category_id = ec.id
                   WHERE YEAR(e.date) = ?";
        $paramsExp = [$year];

        if ($vehicleId) {
            $sqlExp .= " AND (e.vehicle_id = ? OR t.vehicle_id = ?)";
            $paramsExp[] = $vehicleId;
            $paramsExp[] = $vehicleId;
        }
        if ($driverId) {
            $sqlExp .= " AND t.driver_id = ?";
            $paramsExp[] = $driverId;
        }
        $sqlExp .= " GROUP BY m, ec.id, e.category_name, e.category";

        $stmt = $this->pdo->prepare($sqlExp);
        $stmt->execute($paramsExp);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $cat = ucfirst($row['category'] ?? 'otros');
            if (!isset($expenses_by_cat[$cat])) {
                $expenses_by_cat[$cat] = array_fill(1, 12, 0);
            }
            $expenses_by_cat[$cat][$row['m']] = $row['total'];
        }
        ksort($expenses_by_cat);

        // 3. Totals Calculation
        $monthly_utility = [];
        $monthly_expenses_total = [];
        foreach ($months as $m) {
            $sum_exp = $commissions[$m];
            foreach ($expenses_by_cat as $cat => $amounts) {
                $sum_exp += $amounts[$m];
            }
            $monthly_expenses_total[$m] = $sum_exp;
            $monthly_utility[$m] = $income[$m] - $sum_exp;
        }

        $total_income = array_sum($income);
        $total_utility = array_sum($monthly_utility);
        $margin_total = ($total_income > 0) ? ($total_utility / $total_income) * 100 : 0;

        return [
            'months' => $months,
            'income' => $income,
            'total_income' => $total_income,
            'expenses_by_cat' => $expenses_by_cat,
            'commissions' => $commissions,
            'monthly_expenses_total' => $monthly_expenses_total,
            'monthly_utility' => $monthly_utility,
            'total_utility' => $total_utility,
            'margin_total' => $margin_total,
            // Return filters for form persistence
            'filters' => [
                'year' => $year,
                'vehicle_id' => $vehicleId,
                'driver_id' => $driverId
            ],
            // Return lists for dropdowns
            'vehicles' => $this->pdo->query("SELECT * FROM vehicles WHERE active=1")->fetchAll(PDO::FETCH_ASSOC),
            'drivers' => $this->pdo->query("SELECT * FROM personnel WHERE type='Conductor' AND active=1")->fetchAll(PDO::FETCH_ASSOC)
        ];
    }

    /**
     * Retrieves profitability metrics grouped by Route (Origin-Destination)
     */
    public function getRouteProfitability($year = null, $stateId = null)
    {
        $year = $year ?: date('Y');

        try {
            $startDate = "$year-01-01";
            $endDate = "$year-12-31";

            $whereClause = "t.date_load BETWEEN ? AND ?";
            $params = [$startDate, $endDate];

            if ($stateId) {
                $whereClause .= " AND (t.origin_state_id = ? OR t.destination_state_id = ?)";
                $params[] = $stateId;
                $params[] = $stateId;
            }

            // Subquery params for expenses
            $subParams = [$startDate, $endDate];
            $stateFilterSql = $stateId ? " AND (t2.origin_state_id = ? OR t2.destination_state_id = ?)" : "";
            $stateFilterSql3 = $stateId ? " AND (t4.origin_state_id = ? OR t4.destination_state_id = ?)" : "";
            $stateFilterSql2 = $stateId ? " AND (t3.origin_state_id = ? OR t3.destination_state_id = ?)" : "";

            if ($stateId) {
                $subParams[] = $stateId;
                $subParams[] = $stateId;
            }

            $sql = "
                SELECT 
                    t.origin, 
                    t.destination, 
                    so.name as origin_state,
                    sd.name as dest_state,
                    COUNT(t.id) as trip_count,
                    SUM(t.flete_neto) as total_income,
                    SUM(t.commission_value) as total_commissions,
                    (
                        SELECT COALESCE(SUM(e.amount), 0)
                        FROM expenses e
                        JOIN trips t2 ON e.trip_id = t2.id
                        WHERE t2.origin = t.origin AND t2.destination = t.destination
                        AND t2.date_load BETWEEN ? AND ?
                        $stateFilterSql
                    ) as total_expenses,
                    (
                        SELECT COALESCE(SUM(e3.amount), 0)
                        FROM expenses e3
                        JOIN trips t4 ON e3.trip_id = t4.id
                        WHERE t4.origin = t.origin AND t4.destination = t.destination
                        AND e3.category_id IN (SELECT id FROM expense_categories WHERE slug = 'combustible' OR parent_id = (SELECT id FROM expense_categories WHERE slug = 'combustible'))
                        AND t4.date_load BETWEEN ? AND ?
                        $stateFilterSql3
                    ) as fuel_expenses
                FROM trips t
                LEFT JOIN loc_states so ON t.origin_state_id = so.id
                LEFT JOIN loc_states sd ON t.destination_state_id = sd.id
                WHERE $whereClause
                GROUP BY t.origin, t.destination, so.name, sd.name
                ORDER BY (SUM(t.flete_neto) - (SUM(t.commission_value) + (
                    SELECT COALESCE(SUM(e2.amount), 0)
                    FROM expenses e2
                    JOIN trips t3 ON e2.trip_id = t3.id
                    WHERE t3.origin = t.origin AND t3.destination = t.destination
                    AND t3.date_load BETWEEN ? AND ?
                    $stateFilterSql2
                ))) DESC
            ";

            $finalParams = array_merge($subParams, $subParams, $params, $subParams);

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($finalParams);
            $routes = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Calculate Margin % and Net Utility
            foreach ($routes as &$r) {
                $r['net_utility'] = $r['total_income'] - ($r['total_commissions'] + $r['total_expenses']);
                $r['margin'] = ($r['total_income'] > 0) ? ($r['net_utility'] / $r['total_income']) * 100 : 0;
            }

            return $routes;

        } catch (PDOException $e) {
            return ['error' => $e->getMessage()];
        }
    }
}
?>