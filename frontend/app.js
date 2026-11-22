import { ref } from 'vue';
import { apiUrl } from './config.js';
import LoginView from './views/LoginView.js';
import ConsultationsView from './views/ConsultationsView.js';
import VideoCallView from './views/VideoCallView.js';

export default {
    components: { LoginView, ConsultationsView, VideoCallView },
    setup() {
        const authenticated = ref(false);
        const role = ref('user');
        const activeView = ref('consultations');
        const showConsultations = (loginRole) => {
            role.value = loginRole || 'user';
            authenticated.value = true;
            activeView.value = 'consultations';
            window.location.hash = '#consultations';
        };
        const goToVideoCall = () => {
            activeView.value = 'videocall';
            window.location.hash = '#videocall';
        };
        const goToConsultations = () => {
            activeView.value = 'consultations';
            window.location.hash = '#consultations';
        };
        const restoreSession = async () => {
            try {
                const response = await fetch(apiUrl('sesion_actual.php'), { credentials: 'same-origin' });
                if (!response.ok) {
                    if (!authenticated.value && window.location.hash) window.location.hash = '';
                    return;
                }

                const sessionResult = await response.json();
                if (!sessionResult.autenticado) {
                    if (!authenticated.value && window.location.hash) window.location.hash = '';
                    return;
                }

                role.value = sessionResult.rol || 'user';
                authenticated.value = true;
                activeView.value = window.location.hash === '#videocall' ? 'videocall' : 'consultations';
                if (window.location.hash !== '#videocall' && !window.location.hash.startsWith('#consultations?google=')) window.location.hash = '#consultations';
            } catch (error) {
                if (!authenticated.value && window.location.hash) window.location.hash = '';
            }
        };
        window.addEventListener('hashchange', () => {
            if (!authenticated.value && (window.location.hash === '#consultations' || window.location.hash === '#videocall')) {
                window.location.hash = '';
            } else if (window.location.hash === '#consultations') {
                authenticated.value = true;
                activeView.value = 'consultations';
            } else if (window.location.hash === '#videocall') {
                authenticated.value = true;
                activeView.value = 'videocall';
            }
        });
        restoreSession();
        return { authenticated, role, activeView, showConsultations, goToVideoCall, goToConsultations };
    },
    template: `
        <div v-if="!authenticated"><login-view @authenticated="showConsultations"></login-view></div>
        <div v-else>
            <ul class="nav nav-tabs mb-3">
                <li class="nav-item">
                    <button class="nav-link" :class="{ active: activeView === 'consultations' }" @click="goToConsultations">Consultas</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" :class="{ active: activeView === 'videocall' }" @click="goToVideoCall">Videollamada Google Meet</button>
                </li>
            </ul>
            <consultations-view v-if="activeView === 'consultations'" :role="role"></consultations-view>
            <video-call-view v-if="activeView === 'videocall'"></video-call-view>
        </div>
    `,
};
