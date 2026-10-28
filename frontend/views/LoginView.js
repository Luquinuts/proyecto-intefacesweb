import LoginForm from '../components/LoginForm.js';

export default {
    components: { LoginForm },
    emits: ['authenticated'],
    template: `
        <section class="pasos-card">
            <header class="text-center mb-4">
                <p class="brand fw-bold mb-2">PASOS</p>
                <h1 class="display-6">Entiende qué está pasando. Decide cómo seguir.</h1>
                <p class="lead">Una primera orientación legal revisada por una persona. No es asesoría legal automatizada.</p>
            </header>
            <login-form @authenticated="$emit('authenticated', $event)"></login-form>
        </section>
    `,
};
