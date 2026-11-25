<script>
import LoginForm from '../components/LoginForm.vue';

export default {
    components: { LoginForm },
    emits: ['authenticated'],
};
</script>

<template>
        <section class="pasos-card">
            <header class="text-center mb-4">
                <h1 class="display-6">Entiende qué está pasando. Decide cómo seguir.</h1>
                <p class="lead">Una primera orientación legal revisada por una persona. No es asesoría legal automatizada.</p>
            </header>
            <login-form @authenticated="$emit('authenticated', $event)"></login-form>
        </section>
</template>
