import { apiUrl } from '../config.js';

async function requestJson(url, fallbackMessage) {
    try {
        const response = await fetch(url);
        let result = {};
        try {
            result = await response.json();
        } catch (error) {
            result = {};
        }
        if (!response.ok) {
            return { correcto: false, mensaje: result.mensaje || fallbackMessage };
        }
        return result;
    } catch (error) {
        return { correcto: false, mensaje: fallbackMessage };
    }
}

export default {
    data() {
        return {
            meetings: [],
            message: '',
            joinEmail: '',
        };
    },
    mounted() {
        this.loadMeetings();
    },
    methods: {
        async loadMeetings() {
            const result = await requestJson(apiUrl('api/videocall.php'), 'No se pudieron cargar las videollamadas.');
            if (result.correcto) {
                this.meetings = result.meetings || [];
                this.joinEmail = result.join_email || '';
            } else {
                this.message = result.mensaje || 'No hay videollamadas disponibles.';
            }
        },
        isMeetingAvailable(meeting) {
            if (!meeting || !meeting.scheduled_at) return false;
            const scheduled = new Date(String(meeting.scheduled_at).replace(' ', 'T'));
            if (Number.isNaN(scheduled.getTime())) return false;
            return new Date() >= scheduled;
        },
        openMeeting(meeting) {
            if (!this.isMeetingAvailable(meeting)) return;
            if (meeting && meeting.meeting_link) {
                let link = meeting.meeting_link;
                if (this.joinEmail) {
                    const separator = link.includes('?') ? '&' : '?';
                    link += `${separator}authuser=${encodeURIComponent(this.joinEmail)}`;
                }
                window.open(link, '_blank', 'noopener,noreferrer');
            }
        },
    },
    template: `
        <section class="pasos-card">
            <header class="mb-4">
                <h1 class="h3">Videollamadas agendadas</h1>
                <p>Solo se muestran cuando hay una videollamada agendada con preferencia Google Meet.</p>
            </header>
            <div class="card shadow-sm p-3">
                <div v-if="message" class="alert alert-info">{{ message }}</div>
                <div v-if="!meetings.length && !message" class="text-muted">No hay videollamadas agendadas.</div>
                <div v-for="meeting in meetings" :key="meeting.id" class="border-bottom py-2 d-flex justify-content-between align-items-center">
                    <div>
                        <strong>Consulta #{{ meeting.id }}</strong><br>
                        <span>Fecha/Hora: {{ meeting.scheduled_at }}</span><br>
                        <span>Estado: {{ meeting.status }}</span>
                        <div v-if="!isMeetingAvailable(meeting)" class="text-muted small mt-1">El enlace se habilitará en la fecha y hora agendada.</div>
                    </div>
                    <button class="btn btn-primary" :disabled="!isMeetingAvailable(meeting)" @click="openMeeting(meeting)">Unirse a Google Meet</button>
                </div>
            </div>
        </section>
    `,
};
