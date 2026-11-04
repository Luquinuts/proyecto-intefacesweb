export default {
    data() {
        return {
            meetings: [],
            message: '',
        };
    },
    mounted() {
        this.loadMeetings();
    },
    methods: {
        async loadMeetings() {
            const response = await fetch('../backend/api/videocall.php');
            const result = await response.json();
            if (result.correcto) {
                this.meetings = result.meetings || [];
            } else {
                this.message = result.mensaje || 'No hay videollamadas disponibles.';
            }
        },
        openMeeting(link) {
            if (link) {
                window.open(link, '_blank');
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
                    </div>
                    <button class="btn btn-primary" @click="openMeeting(meeting.meeting_link)">Unirse a Google Meet</button>
                </div>
            </div>
        </section>
    `,
};
