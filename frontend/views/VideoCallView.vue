<script>
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
            now: Date.now(),
            clockTimer: null,
        };
    },
    mounted() {
        this.loadMeetings();
        this.clockTimer = setInterval(() => { this.now = Date.now(); }, 1000);
    },
    beforeUnmount() { clearInterval(this.clockTimer); },
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
            return !!meeting?.can_join && this.now < Date.parse(meeting.ends_at);
        },
        meetingLabel(meeting) {
            if (meeting.status === 'closed' || this.now >= Date.parse(meeting.ends_at)) return 'Videollamada finalizada.';
            if (this.now < Date.parse(meeting.scheduled_at_iso)) return 'Disponible en la fecha y hora agendada (Buenos Aires).';
            return this.isMeetingAvailable(meeting) ? 'Disponible durante 30 minutos.' : 'Actualizá para comprobar el acceso.';
        },
        openMeeting(meeting) {
            if (!this.isMeetingAvailable(meeting)) return;
            window.open(apiUrl(`api/join_meeting.php?id=${meeting.id}`), '_blank', 'noopener,noreferrer');
        },
    },
};
</script>

<template>
        <section class="pasos-card">
            <header class="mb-4">
                <h1 class="h3">Videollamadas agendadas</h1>
                <p>Solo se muestran cuando hay una videollamada agendada con preferencia Google Meet.</p>
            </header>
            <div class="card shadow-sm p-3">
                <button type="button" class="btn btn-outline-secondary btn-sm mb-2" @click="loadMeetings">Actualizar videollamadas</button>
                <div v-if="message" class="alert alert-info">{{ message }}</div>
                <div v-if="!meetings.length && !message" class="text-muted">No hay videollamadas agendadas.</div>
                <div v-for="meeting in meetings" :key="meeting.id" class="border-bottom py-2 d-flex justify-content-between align-items-center">
                    <div>
                        <strong>Consulta #{{ meeting.id }}</strong><br>
                        <span>Fecha/Hora: {{ meeting.scheduled_at }}</span><br>
                        <span>Estado: {{ meeting.status }}</span>
                        <div class="small mt-1" :class="['Videollamada finalizada.', 'Disponible en la fecha y hora agendada (Buenos Aires).', 'Disponible durante 30 minutos.'].includes(meetingLabel(meeting)) ? 'text-white' : 'text-muted'" role="status">{{ meetingLabel(meeting) }}</div>
                    </div>
                    <button class="btn btn-primary" :disabled="!isMeetingAvailable(meeting)" @click="openMeeting(meeting)">Unirse a Google Meet</button>
                </div>
            </div>
        </section>
</template>
