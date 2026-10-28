import LoginView from './views/LoginView.js';
import ConsultationsView from './views/ConsultationsView.js';

const { createApp, ref } = Vue;

createApp({
    components: { LoginView, ConsultationsView },
    setup() {
        const authenticated = ref(window.location.hash === '#consultations');
        const role = ref('user');
        const showConsultations = (loginRole) => {
            role.value = loginRole || 'user';
            authenticated.value = true;
            window.location.hash = '#consultations';
        };
        window.addEventListener('hashchange', () => {
            if (window.location.hash === '#consultations') {
                authenticated.value = true;
            }
        });
        return { authenticated, role, showConsultations };
    },
    template: `
        <div v-if="!authenticated"><login-view @authenticated="showConsultations"></login-view></div>
        <div v-else><consultations-view :role="role"></consultations-view></div>
    `,
}).mount('#app');
