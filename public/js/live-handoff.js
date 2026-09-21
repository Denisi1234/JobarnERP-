// FrontDesk OS — Real-Time Live Handoff & Broadcast Engine
// Connects Reception, IT Service Desk, Sales Billing, and Manager Command Center

class LiveHandoffEngine {
    constructor() {
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        this.pollInterval = 4000; // 4 seconds polling for real-time reactive sync
        this.timer = null;
        this.subscribers = new Set();
        this.lastStateHash = '';
        this.isPolling = false;
    }

    init() {
        this.fetchState();
        this.startPolling();
    }

    startPolling() {
        if (this.timer) clearInterval(this.timer);
        this.timer = setInterval(() => this.fetchState(), this.pollInterval);
    }

    stopPolling() {
        if (this.timer) clearInterval(this.timer);
    }

    subscribe(callback) {
        this.subscribers.add(callback);
        return () => this.subscribers.delete(callback);
    }

    notifySubscribers(data) {
        this.subscribers.forEach(cb => {
            try {
                cb(data);
            } catch (err) {
                console.error('[LiveHandoffEngine] Subscriber callback error:', err);
            }
        });
    }

    async fetchState() {
        if (this.isPolling) return;
        this.isPolling = true;
        try {
            const res = await fetch('/api/live/state', {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken
                }
            });
            if (res.ok) {
                const data = await res.json();
                const hash = JSON.stringify(data.stats) + '-' + (data.active_visits?.length || 0);
                if (hash !== this.lastStateHash) {
                    this.lastStateHash = hash;
                    this.notifySubscribers(data);
                }
            }
        } catch (err) {
            // Silently fallback on intermittent network
        } finally {
            this.isPolling = false;
        }
    }

    async createVisit(payload) {
        const res = await fetch('/api/live/visits', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': this.csrfToken
            },
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        this.fetchState();
        return data;
    }

    async forwardToDepartment(visitId, payload) {
        const res = await fetch(`/api/live/visits/${visitId}/services`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': this.csrfToken
            },
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        this.fetchState();
        return data;
    }

    async acceptTask(serviceId) {
        const res = await fetch(`/api/live/services/${serviceId}/accept`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': this.csrfToken
            }
        });
        const data = await res.json();
        this.fetchState();
        return data;
    }

    async completeTaskWithPrice(serviceId, price, notes = '') {
        const res = await fetch(`/api/live/services/${serviceId}/complete`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': this.csrfToken
            },
            body: JSON.stringify({ price: parseInt(price, 10), resolution_notes: notes })
        });
        const data = await res.json();
        this.fetchState();
        return data;
    }

    async checkoutVisit(visitId, paymentStatus = 'paid', notes = '') {
        const res = await fetch(`/api/live/visits/${visitId}/checkout`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': this.csrfToken
            },
            body: JSON.stringify({ payment_status: paymentStatus, notes: notes })
        });
        const data = await res.json();
        this.fetchState();
        return data;
    }

    async getTimeline(visitId) {
        const res = await fetch(`/api/live/visits/${visitId}/timeline`, {
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': this.csrfToken
            }
        });
        return await res.json();
    }
}

window.LiveHandoff = new LiveHandoffEngine();
document.addEventListener('DOMContentLoaded', () => {
    window.LiveHandoff.init();
});
