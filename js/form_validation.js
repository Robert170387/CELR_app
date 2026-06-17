/**
 * Form Validation Module
 * Client-side validation before form submission
 */

const FormValidator = {
    /**
     * Validate trip form
     */
    validateTripForm(form) {
        const errors = [];
        
        // Required fields
        const requiredFields = [
            { field: 'trip_type', label: 'Tipo de Viaje' },
            { field: 'vehicle_id', label: 'Vehículo' },
            { field: 'driver_id', label: 'Conductor' },
            { field: 'client_id', label: 'Cliente' },
            { field: 'origin', label: 'Origen' },
            { field: 'destination', label: 'Destino' },
            { field: 'date_load', label: 'Fecha de Carga' }
        ];
        
        requiredFields.forEach(({ field, label }) => {
            const value = form[field]?.value?.trim();
            if (!value) {
                errors.push(`El campo '${label}' es obligatorio.`);
            }
        });
        
        // Numeric validations
        const numericFields = [
            { field: 'kms_start', label: 'Kilometraje Inicial', min: 0 },
            { field: 'flete_bruto', label: 'Flete Bruto', min: 0 },
            { field: 'percent_rete_fuente', label: 'Rete Fuente %', min: 0, max: 100 },
            { field: 'percent_rete_ica', label: 'Rete ICA %', min: 0, max: 100 }
        ];
        
        numericFields.forEach(({ field, label, min, max }) => {
            const value = parseFloat(form[field]?.value);
            if (isNaN(value)) {
                errors.push(`El campo '${label}' debe ser un número válido.`);
            } else if (min !== undefined && value < min) {
                errors.push(`El campo '${label}' no puede ser menor a ${min}.`);
            } else if (max !== undefined && value > max) {
                errors.push(`El campo '${label}' no puede ser mayor a ${max}.`);
            }
        });
        
        // Date validations
        const dateLoad = form.date_load?.value;
        const dateUnload = form.date_unload?.value;
        
        if (dateLoad && dateUnload) {
            if (new Date(dateUnload) < new Date(dateLoad)) {
                errors.push('La fecha de descarga no puede ser anterior a la fecha de carga.');
            }
        }
        
        // Kilometer validation
        const kmsStart = parseFloat(form.kms_start?.value);
        const kmsEnd = parseFloat(form.kms_end?.value);
        
        if (!isNaN(kmsStart) && !isNaN(kmsEnd) && kmsEnd < kmsStart) {
            errors.push('El kilometraje final no puede ser menor al inicial.');
        }
        
        return errors;
    },
    
    /**
     * Validate expense form
     */
    validateExpenseForm(form) {
        const errors = [];
        
        // Required fields
        if (!form.category?.value?.trim()) {
            errors.push('La categoría es obligatoria.');
        }
        
        if (!form.vehicle_id?.value) {
            errors.push('El vehículo es obligatorio.');
        }
        
        // Amount validation
        const amount = parseFloat(form.amount?.value);
        if (isNaN(amount) || amount <= 0) {
            errors.push('El monto debe ser un número positivo.');
        }
        
        // Date validation
        const date = form.date?.value;
        if (!date) {
            errors.push('La fecha es obligatoria.');
        } else {
            const dateObj = new Date(date);
            const today = new Date();
            today.setHours(23, 59, 59, 999);
            
            if (dateObj > today) {
                errors.push('La fecha no puede ser futura.');
            }
        }
        
        return errors;
    },
    
    /**
     * Validate client form
     */
    validateClientForm(form) {
        const errors = [];
        
        const personType = form.person_type?.value;
        
        if (personType === 'Jurídica') {
            if (!form.business_name?.value?.trim()) {
                errors.push('El nombre de la empresa es obligatorio.');
            }
        } else {
            if (!form.firstname?.value?.trim()) {
                errors.push('El nombre es obligatorio.');
            }
            if (!form.lastname1?.value?.trim()) {
                errors.push('El apellido es obligatorio.');
            }
        }
        
        // Email validation
        const email = form.email?.value?.trim();
        if (email && !this.isValidEmail(email)) {
            errors.push('El formato del correo electrónico no es válido.');
        }
        
        // Phone validation (basic)
        const phone = form.phone?.value?.trim();
        if (phone && !this.isValidPhone(phone)) {
            errors.push('El formato del teléfono no es válido.');
        }
        
        return errors;
    },
    
    /**
     * Validate vehicle form
     */
    validateVehicleForm(form) {
        const errors = [];
        
        if (!form.placa?.value?.trim()) {
            errors.push('La placa es obligatoria.');
        }
        
        if (!form.brand?.value?.trim()) {
            errors.push('La marca es obligatoria.');
        }
        
        const year = parseInt(form.year?.value);
        if (isNaN(year) || year < 1900 || year > new Date().getFullYear() + 1) {
            errors.push('El año no es válido.');
        }
        
        return errors;
    },
    
    /**
     * Validate settlement form
     */
    validateSettlementForm(form) {
        const errors = [];
        
        const finalPay = parseFloat(form.final_pay?.value);
        if (isNaN(finalPay) || finalPay < 0) {
            errors.push('El pago final debe ser un número válido.');
        }
        
        return errors;
    },
    
    /**
     * Generic form validation wrapper
     */
    validate(form, formType) {
        let errors = [];
        
        switch (formType) {
            case 'trip':
                errors = this.validateTripForm(form);
                break;
            case 'expense':
                errors = this.validateExpenseForm(form);
                break;
            case 'client':
                errors = this.validateClientForm(form);
                break;
            case 'vehicle':
                errors = this.validateVehicleForm(form);
                break;
            case 'settlement':
                errors = this.validateSettlementForm(form);
                break;
            default:
                errors = this.validateRequiredFields(form);
        }
        
        return errors;
    },
    
    /**
     * Validate all required fields in a form
     */
    validateRequiredFields(form) {
        const errors = [];
        const requiredElements = form.querySelectorAll('[required]');
        
        requiredElements.forEach(element => {
            if (!element.value?.trim()) {
                const label = this.getFieldLabel(element) || element.name || element.id || 'Campo';
                errors.push(`El campo '${label}' es obligatorio.`);
                element.classList.add('border-red-500');
            } else {
                element.classList.remove('border-red-500');
            }
        });
        
        return errors;
    },
    
    /**
     * Get label for a form field
     */
    getFieldLabel(element) {
        // Try to find associated label
        const id = element.id;
        if (id) {
            const label = document.querySelector(`label[for="${id}"]`);
            if (label) return label.textContent.trim();
        }
        
        // Try parent label
        const parentLabel = element.closest('label');
        if (parentLabel) return parentLabel.textContent.trim();
        
        // Try placeholder
        return element.placeholder || element.getAttribute('aria-label');
    },
    
    /**
     * Email validation
     */
    isValidEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    },
    
    /**
     * Phone validation (basic)
     */
    isValidPhone(phone) {
        const re = /^[\d\s\-\+\(\)]{7,20}$/;
        return re.test(phone);
    },
    
    /**
     * Show validation errors
     */
    showErrors(errors, container = null) {
        if (!container) {
            container = document.getElementById('validation-errors');
        }
        
        if (!container) {
            // Create error container if it doesn't exist
            container = document.createElement('div');
            container.id = 'validation-errors';
            container.className = 'mb-4 p-4 bg-red-50 border border-red-200 rounded-lg';
            
            const firstForm = document.querySelector('form');
            if (firstForm) {
                firstForm.insertBefore(container, firstForm.firstChild);
            }
        }
        
        if (errors.length === 0) {
            container.style.display = 'none';
            return;
        }
        
        container.innerHTML = `
            <div class="flex items-start">
                <svg class="w-5 h-5 text-red-500 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
                <div>
                    <h4 class="text-sm font-bold text-red-800 mb-1">Por favor corrija los siguientes errores:</h4>
                    <ul class="list-disc list-inside text-sm text-red-700 space-y-1">
                        ${errors.map(error => `<li>${error}</li>`).join('')}
                    </ul>
                </div>
            </div>
        `;
        container.style.display = 'block';
        
        // Scroll to errors
        container.scrollIntoView({ behavior: 'smooth', block: 'start' });
    },
    
    /**
     * Clear validation errors
     */
    clearErrors() {
        const container = document.getElementById('validation-errors');
        if (container) {
            container.style.display = 'none';
        }
        
        // Remove error styling from fields
        document.querySelectorAll('.border-red-500').forEach(el => {
            el.classList.remove('border-red-500');
        });
    },
    
    /**
     * Initialize form validation
     */
    init(formSelector, formType) {
        const form = document.querySelector(formSelector);
        if (!form) return;
        
        form.addEventListener('submit', (e) => {
            this.clearErrors();
            
            const errors = this.validate(form, formType);
            
            if (errors.length > 0) {
                e.preventDefault();
                this.showErrors(errors);
                return false;
            }
            
            return true;
        });
        
        // Real-time validation on blur
        form.querySelectorAll('input, select, textarea').forEach(field => {
            field.addEventListener('blur', () => {
                field.classList.remove('border-red-500');
            });
        });
    }
};

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = FormValidator;
}
