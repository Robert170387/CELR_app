<?php
// app/Core/MigrationManager.php

class MigrationManager
{
    private $pdo;
    private $migrationDir;

    public function __construct($pdo, $migrationDir)
    {
        $this->pdo = $pdo;
        $this->migrationDir = $migrationDir;
        $this->ensureMigrationTable();
    }

    private function ensureMigrationTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS migrations_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            migration_name VARCHAR(255) NOT NULL,
            executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        $this->pdo->exec($sql);
    }

    public function getAppliedMigrations()
    {
        $stmt = $this->pdo->query("SELECT migration_name FROM migrations_log");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function getPendingMigrationsCount()
    {
        $applied = $this->getAppliedMigrations();
        $files = scandir($this->migrationDir);
        $count = 0;

        foreach ($files as $file) {
            if ($file === '.' || $file === '..')
                continue;
            if (!in_array($file, $applied)) {
                $count++;
            }
        }
        return $count;
    }

    public function migrate()
    {
        $applied = $this->getAppliedMigrations();
        $files = scandir($this->migrationDir);

        $pending = [];
        foreach ($files as $file) {
            if ($file === '.' || $file === '..')
                continue;
            // Only process .sql or .php files that start with date or number
            // Naming convention: YYYYMMDD_HHMM_description.sql OR 001_description.php
            if (!in_array($file, $applied)) {
                $pending[] = $file;
            }
        }

        sort($pending); // Ensure execution order

        $results = [];

        foreach ($pending as $file) {
            try {
                $path = $this->migrationDir . '/' . $file;
                $ext = pathinfo($path, PATHINFO_EXTENSION);

                echo "Executing $file... ";

                if ($ext === 'sql') {
                    $sql = file_get_contents($path);
                    $this->pdo->exec($sql);
                } elseif ($ext === 'php') {
                    // Make $pdo available to the included script
                    $pdo = $this->pdo;
                    require $path;
                }

                $stmt = $this->pdo->prepare("INSERT INTO migrations_log (migration_name) VALUES (?)");
                $stmt->execute([$file]);

                echo "OK\n";
                $results[] = ["status" => "success", "file" => $file];

            } catch (Exception $e) {
                echo "FAILED: " . $e->getMessage() . "\n";
                $results[] = ["status" => "error", "file" => $file, "message" => $e->getMessage()];
                break; // Stop on first error
            }
        }

        if (empty($pending)) {
            echo "No pending migrations.\n";
        }

        return $results;
    }
}
?>