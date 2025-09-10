import './bootstrap';
import Alpine from 'alpinejs';

// Alpine.js plugins
import focus from '@alpinejs/focus'
import persist from '@alpinejs/persist'
import collapse from '@alpinejs/collapse'

Alpine.plugin(focus)
Alpine.plugin(persist)
Alpine.plugin(collapse)

// Global Alpine components
Alpine.data('dropdown', () => ({
    open: false,
    toggle() {
        this.open = !this.open
    },
    close() {
        this.open = false
    }
}))

Alpine.data('modal', () => ({
    open: false,
    show() {
        this.open = true
        document.body.style.overflow = 'hidden'
    },
    hide() {
        this.open = false
        document.body.style.overflow = 'auto'
    }
}))

Alpine.data('notification', () => ({
    visible: true,
    message: '',
    type: 'info', // success, error, warning, info
    show(message, type = 'info') {
        this.message = message
        this.type = type
        this.visible = true
        setTimeout(() => this.hide(), 5000)
    },
    hide() {
        this.visible = false
    }
}))

Alpine.data('sidebar', () => ({
    open: window.innerWidth > 768,
    toggle() {
        this.open = !this.open
        if (window.innerWidth <= 768) {
            // Save state for mobile
            localStorage.setItem('sidebarOpen', this.open)
        }
    },
    init() {
        // Responsive behavior
        window.addEventListener('resize', () => {
            if (window.innerWidth > 768) {
                this.open = true
            } else {
                this.open = localStorage.getItem('sidebarOpen') === 'true'
            }
        })
    }
}))

window.Alpine = Alpine
Alpine.start()
