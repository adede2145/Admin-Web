class AutoRefresh {
    constructor(options = {}) {
        this.interval = options.interval || 30000; // Default 30 seconds
        this.endpoints = new Map();
        this.running = false;
    }

    // Add an endpoint to refresh
    addEndpoint(selector, url, callback) {
        this.endpoints.set(selector, { url, callback });
    }

    // Start the auto-refresh
    start() {
        if (this.running) return;
        this.running = true;
        
        this.refresh();
        this.timer = setInterval(() => this.refresh(), this.interval);
    }

    // Stop the auto-refresh
    stop() {
        if (this.timer) {
            clearInterval(this.timer);
            this.timer = null;
        }
        this.running = false;
    }

    // Perform the refresh for all registered endpoints
    async refresh() {
        for (const [selector, config] of this.endpoints) {
            try {
                const response = await fetch(config.url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                
                if (config.callback) {
                    config.callback(data);
                } else {
                    // Default behavior: replace content
                    const element = document.querySelector(selector);
                    if (element && data.html) {
                        element.innerHTML = data.html;
                    }
                }
            } catch (error) {
                console.error('Auto-refresh failed:', error);
            }
        }
    }
}

// Export for use in other files
window.AutoRefresh = AutoRefresh;