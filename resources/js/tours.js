class TourManager {
    constructor() {
        this.element = document.getElementById('tour-manager');
        if (!this.element) return;

        this.tours = JSON.parse(this.element.dataset.tours || '{}');
        this.progress = JSON.parse(this.element.dataset.progress || '[]');
        this.routeName = this.element.dataset.route || '';

        this.initializedTours = {};

        this.init();
    }

    init() {
        this.autoStartContextual();
        this.autoStartOnboarding();
        this.exposeGlobal();
    }

    isCompleted(tourId) {
        return this.progress.some(p => p.tour_id === tourId && p.completed_at);
    }

    isSkipped(tourId) {
        return this.progress.some(p => p.tour_id === tourId && p.skipped_at);
    }

    autoStartOnboarding() {
        if (this.routeName !== 'dashboard') return;
        const tour = this.tours['onboarding'];
        if (!tour) return;
        if (this.isCompleted('onboarding') || this.isSkipped('onboarding')) return;

        setTimeout(() => this.start('onboarding'), 500);
    }

    autoStartContextual() {
        const tour = Object.values(this.tours).find(
            t => t.type === 'contextual' && t.routes?.includes(this.routeName)
        );
        if (!tour) return;
        if (this.isCompleted(tour.id) || this.isSkipped(tour.id)) return;

        setTimeout(() => this.start(tour.id), 500);
    }

    exposeGlobal() {
        window.startTour = (tourId) => this.start(tourId);
    }

    start(tourId) {
        const config = this.tours[tourId];
        if (!config) {
            console.warn(`Tour "${tourId}" not found in config.`);
            return;
        }

        const steps = config.steps.map(step => ({
            element: step.element,
            popover: {
                title: step.title,
                description: step.description,
                side: step.position || 'bottom',
                align: 'start',
            },
        }));

        const driver = window.driver.js.driver({
            showProgress: true,
            steps,
            onDestroyed: () => {
                Livewire.dispatch('markCompleted', { tourId });
            },
            onPopoverRender: (popover) => {
                const closeBtn = document.createElement('button');
                closeBtn.innerHTML = '&times;';
                closeBtn.className = 'driver-close-btn';
                closeBtn.setAttribute('aria-label', 'Close');
                closeBtn.onclick = () => {
                    driver.destroy();
                    Livewire.dispatch('markSkipped', { tourId });
                };
                popover.wrapper.querySelector('.driver-popover-title')?.appendChild(closeBtn);
            },
        });

        driver.drive();
    }
}

document.addEventListener('DOMContentLoaded', () => {
    new TourManager();
});
