import LoginForm from '../components/LoginForm.js';

export default {
    components: { LoginForm },
    emits: ['authenticated'],
    template: `
        <section class="pasos-card">
            <header class="text-center mb-4">
                <p class="brand fw-bold mb-2">PASOS</p>
                <h1 class="display-6">Understand what is happening. Decide how to continue.</h1>
                <p class="lead">A first legal orientation reviewed by a person. It is not automated legal advice.</p>
            </header>
            <login-form @authenticated="$emit('authenticated', $event)"></login-form>
        </section>
    `,
};
