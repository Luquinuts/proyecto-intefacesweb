<script>
import { apiUrl } from './config.js';
import LoginView from './views/LoginView.vue';
import ConsultationsView from './views/ConsultationsView.vue';
import VideoCallView from './views/VideoCallView.vue';

export default {
    components: { LoginView, ConsultationsView, VideoCallView },
    data() {
        return { authenticated: false, role: 'user', activeView: 'consultations' };
    },
    beforeMount() {
        this.restoreSession();
    },
    mounted() {
        window.addEventListener('hashchange', this.handleHashChange);
    },
    beforeUnmount() {
        window.removeEventListener('hashchange', this.handleHashChange);
    },
    methods: {
        showConsultations(loginRole) {
            this.role = loginRole || 'user';
            this.authenticated = true;
            this.activeView = 'consultations';
            window.location.hash = '#consultations';
        },
        goToVideoCall() {
            this.activeView = 'videocall';
            window.location.hash = '#videocall';
        },
        goToConsultations() {
            this.activeView = 'consultations';
            window.location.hash = '#consultations';
        },
        async restoreSession() {
            try {
                const response = await fetch(apiUrl('sesion_actual.php'), { credentials: 'same-origin' });
                if (!response.ok) {
                    if (!this.authenticated && window.location.hash) window.location.hash = '';
                    return;
                }

                const sessionResult = await response.json();
                if (!sessionResult.autenticado) {
                    if (!this.authenticated && window.location.hash) window.location.hash = '';
                    return;
                }

                this.role = sessionResult.rol || 'user';
                this.authenticated = true;
                this.activeView = window.location.hash === '#videocall' ? 'videocall' : 'consultations';
                if (window.location.hash !== '#videocall' && !window.location.hash.startsWith('#consultations?google=')) window.location.hash = '#consultations';
            } catch (error) {
                if (!this.authenticated && window.location.hash) window.location.hash = '';
            }
        },
        handleHashChange() {
            if (!this.authenticated && (window.location.hash === '#consultations' || window.location.hash === '#videocall')) {
                window.location.hash = '';
            } else if (window.location.hash === '#consultations') {
                this.authenticated = true;
                this.activeView = 'consultations';
            } else if (window.location.hash === '#videocall') {
                this.authenticated = true;
                this.activeView = 'videocall';
            }
        },
    },
};
</script>

<template>
        <div v-if="!authenticated"><login-view @authenticated="showConsultations"></login-view></div>
        <div v-else>
            <div class="d-flex gap-2 mb-3">
                <button class="btn" :class="activeView === 'consultations' ? 'btn-outline-secondary' : 'btn-primary'" @click="goToConsultations">Consultas</button>
                <button class="btn" :class="activeView === 'videocall' ? 'btn-outline-secondary' : 'btn-primary'" @click="goToVideoCall">Videollamada Google Meet</button>
            </div>
            <consultations-view v-if="activeView === 'consultations'" :role="role"></consultations-view>
            <video-call-view v-if="activeView === 'videocall'"></video-call-view>
        </div>
</template>
