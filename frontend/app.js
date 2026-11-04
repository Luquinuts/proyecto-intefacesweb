import LoginView from './views/LoginView.js';
import ConsultationsView from './views/ConsultationsView.js';
import VideoCallView from './views/VideoCallView.js';

const { createApp, ref } = Vue;

createApp({
    components: { LoginView, ConsultationsView, VideoCallView },
    setup() {
        const authenticated = ref(window.location.hash === '#consultations' || window.location.hash === '#videocall');
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
        window.addEventListener('hashchange', () => {
            if (window.location.hash === '#consultations') {
                authenticated.value = true;
                activeView.value = 'consultations';
            } else if (window.location.hash === '#videocall') {
                authenticated.value = true;
                activeView.value = 'videocall';
            }
        });
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
}).mount('#app');
