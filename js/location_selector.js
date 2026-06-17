function locationSelector(defaultCountry = '', defaultState = '', defaultCity = '') {
    return {
        countries: [],
        states: [],
        cities: [],

        selectedCountry: defaultCountry,
        selectedState: defaultState,
        selectedCity: defaultCity,

        isLoadingCountries: false,
        isLoadingStates: false,
        isLoadingCities: false,

        async init() {
            // Store target values to recover after async loads
            const targetCountry = this.selectedCountry;
            const targetState = this.selectedState;
            const targetCity = this.selectedCity;

            await this.fetchCountries();

            if (targetCountry) {
                this.selectedCountry = targetCountry;
                await this.onCountryChange(false);
                
                // Esperar a que Alpine.js actualice el DOM con los estados
                await this.$nextTick();
                
                if (targetState) {
                    this.selectedState = targetState;
                    await this.onStateChange(false);
                    
                    // Esperar a que Alpine.js actualice el DOM con las ciudades
                    await this.$nextTick();
                    
                    if (targetCity) {
                        this.selectedCity = targetCity;
                    }
                }
            }
        },

        async fetchCountries() {
            this.isLoadingCountries = true;
            try {
                const response = await fetch('api_locations.php?action=countries');
                const data = await response.json();
                if (!data.error) {
                    this.countries = data.data;
                }
            } catch (e) {
                console.error('Error loading countries:', e);
            } finally {
                this.isLoadingCountries = false;
            }
        },

        async onCountryChange(clearChildren = true) {
            if (clearChildren) {
                this.selectedState = '';
                this.selectedCity = '';
                this.states = [];
                this.cities = [];
            }

            if (!this.selectedCountry) return;

            this.isLoadingStates = true;
            try {
                const response = await fetch(`api_locations.php?action=states&country=${encodeURIComponent(this.selectedCountry)}`);
                const data = await response.json();
                if (!data.error) {
                    this.states = data.data.states;
                }
            } catch (e) {
                console.error('Error loading states:', e);
            } finally {
                this.isLoadingStates = false;
            }
        },

        async onStateChange(clearChildren = true) {
            if (clearChildren) {
                this.selectedCity = '';
                this.cities = [];
            }

            if (!this.selectedState) return;

            this.isLoadingCities = true;
            try {
                const response = await fetch(`api_locations.php?action=cities&country=${encodeURIComponent(this.selectedCountry)}&state=${encodeURIComponent(this.selectedState)}`);
                const data = await response.json();
                if (!data.error) {
                    this.cities = data.data;
                }
            } catch (e) {
                console.error('Error loading cities/municipalities:', e);
            } finally {
                this.isLoadingCities = false;
            }
        }
    }
}
