import ConsultationTable from '../components/ConsultationTable.js';

export default {
    components: { ConsultationTable },
    props: { role: { type: String, default: 'user' } },
    data() {
        return {
            consultations: [],
            description: '',
            contactPreference: 'email',
            selected: null,
            updateStatus: 'received',
            professionalNote: '',
            message: '',
            scheduleDateTime: '',
            meetingLink: '',
        };
    },
    mounted() {
        this.loadConsultations();
        this.submitHandler = (event) => this.submitConsultation(event);
        $('#consultation-form').on('submit', this.submitHandler);
    },
    beforeUnmount() { $('#consultation-form').off('submit', this.submitHandler); },
    updated() {
        this.$nextTick(() => {
            if (this.role === 'professional' && (this.selected && (this.selected.contact_preference === 'phone' || this.selected.contact_preference === 'video'))) {
                const calEl = document.getElementById('schedule-calendar');
                if (calEl && !calEl.dataset.init) {
                    calEl.dataset.init = '1';
                    const calendar = new FullCalendar.Calendar(calEl, {
                        initialView: 'dayGridMonth',
                        locale: 'es',
                        height: 'auto',
                        headerToolbar: {
                            left: 'prev,next today',
                            center: 'title',
                            right: 'dayGridMonth,timeGridWeek,timeGridDay'
                        },
                        dateClick: (info) => {
                            const now = new Date();
                            const date = info.dateStr;
                            const hours = String(now.getHours()).padStart(2, '0');
                            const minutes = String(now.getMinutes()).padStart(2, '0');
                            this.scheduleDateTime = `${date}T${hours}:${minutes}`;
                        }
                    });
                    calendar.render();
                }
            }
        });
    },
    methods: {
        logout() {
            fetch('../backend/cerrar_sesion.php', { method: 'POST' })
                .finally(() => {
                    window.location.hash = '';
                    window.location.reload();
                });
        },
        async loadConsultations() {
            const response = await fetch('../backend/api/consultations.php');
            const result = await response.json();
            if (result.correcto) this.consultations = result.consultations;
        },
        selectConsultation(consultation) {
            this.selected = consultation;
            this.updateStatus = consultation.status;
            this.professionalNote = consultation.professional_note || '';
            this.scheduleDateTime = consultation.scheduled_at ? consultation.scheduled_at.replace(' ', 'T').substring(0, 16) : '';
            this.meetingLink = consultation.meeting_link || '';
        },
        async submitConsultation(event) {
            event.preventDefault();
            this.message = '';
            const response = await fetch('../backend/api/consultations.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ description: this.description, contact_preference: this.contactPreference }),
            });
            const result = await response.json();
            if (result.correcto) {
                this.description = '';
                this.message = 'Consulta enviada.';
                this.loadConsultations();
            } else this.message = result.mensaje || 'No se pudo enviar la consulta.';
        },
        async updateConsultation(event) {
            event.preventDefault();
            const response = await fetch(`../backend/api/consultations.php/${this.selected.id}`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ status: this.updateStatus, professional_note: this.professionalNote }),
            });
            const result = await response.json();
            this.message = result.correcto ? 'Consulta actualizada.' : (result.mensaje || 'No se pudo actualizar la consulta.');
            if (result.correcto) {
                await this.loadConsultations();
                this.selected = this.consultations.find((consultation) => consultation.id === this.selected.id) || null;
            }
        },
        async scheduleConsultation() {
            if (!this.scheduleDateTime) {
                this.message = 'Selecciona fecha y hora.';
                return;
            }
            const payload = { scheduled_at: this.scheduleDateTime };
            if (this.selected.contact_preference === 'video' && this.meetingLink) {
                payload.meeting_link = this.meetingLink;
            }
            const response = await fetch(`../backend/api/consultations.php/${this.selected.id}`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload),
            });
            const result = await response.json();
            this.message = result.correcto ? 'Cita agendada.' : (result.mensaje || 'No se pudo agendar la cita.');
            if (result.correcto) {
                await this.loadConsultations();
                this.selected = this.consultations.find((consultation) => consultation.id === this.selected.id) || null;
            }
        },
        getStatusLabel(status) {
            const labels = {
                received: 'Recibida',
                under_review: 'En revisión',
                scheduled: 'Agendada',
                closed: 'Cerrada',
            };
            return labels[status] || status;
        },
        getContactLabel(pref) {
            const labels = {
                email: 'Correo electrónico',
                phone: 'Teléfono',
                video: 'Videollamada',
            };
            return labels[pref] || pref;
        },
    },
    template: `
        <section class="pasos-card">
            <header class="mb-4 d-flex justify-content-between align-items-start">
                <div>
                    <h1 class="h3">Tu siguiente paso razonable empieza por entender.</h1>
                </div>
                <button class="btn btn-outline-secondary btn-sm" @click="logout">Cerrar sesión</button>
            </header>
            <form v-if="role !== 'professional'" id="consultation-form" class="card shadow-sm p-3 mb-4">
                <label class="form-label" for="description">¿Qué ocurrió?</label>
                <textarea id="description" v-model.trim="description" class="form-control mb-3" rows="4" maxlength="3000" required></textarea>
                <label class="form-label" for="contact-preference">Preferencia de contacto</label>
                <select id="contact-preference" v-model="contactPreference" class="form-select mb-3">
                    <option value="email">Correo electrónico</option><option value="phone">Teléfono</option><option value="video">Videollamada</option>
                </select>
                <button class="btn btn-primary">Enviar consulta</button>
                <p v-if="message" class="alert alert-info mt-3 mb-0">{{ message }}</p>
            </form>
            <consultation-table :consultations="consultations" @select="selectConsultation"></consultation-table>
            <article v-if="selected" class="alert alert-secondary mt-3">
                <strong>Consulta seleccionada:</strong> {{ selected.description }}<br>
                <span>Preferencia de contacto: {{ getContactLabel(selected.contact_preference) }}</span><br>
                <span>Estado: {{ getStatusLabel(selected.status) }}</span><br>
                <span v-if="selected.scheduled_at">Fecha/hora: {{ selected.scheduled_at }}</span><br v-if="selected.scheduled_at">
                <span v-if="selected.meeting_link">Enlace: <a :href="selected.meeting_link" target="_blank">{{ selected.meeting_link }}</a></span><br v-if="selected.meeting_link">
                <div v-if="role === 'professional' && (selected.contact_preference === 'phone' || selected.contact_preference === 'video')" class="mt-3">
                    <label class="form-label">Agendar cita con calendario</label>
                    <div id="schedule-calendar" class="mb-2"></div>
                    <input type="datetime-local" v-model="scheduleDateTime" class="form-control mb-2" style="max-width: 280px;">
                    <input v-if="selected.contact_preference === 'video'" type="url" v-model="meetingLink" class="form-control mb-2" placeholder="Enlace de Google Meet" style="max-width: 320px;">
                    <button class="btn btn-secondary btn-sm" @click="scheduleConsultation">Agendar</button>
                </div>
                <form v-if="role === 'administrator' || role === 'professional'" id="consultation-update-form" class="mt-3" @submit="updateConsultation">
                    <label class="form-label" for="consultation-status">Estado</label>
                    <select id="consultation-status" v-model="updateStatus" class="form-select mb-2">
                        <option value="received">Recibida</option><option value="under_review">En revisión</option><option value="scheduled">Agendada</option><option value="closed">Cerrada</option>
                    </select>
                    <label class="form-label" for="professional-note">Nota del profesional</label>
                    <textarea id="professional-note" v-model.trim="professionalNote" class="form-control mb-2" rows="3" maxlength="3000"></textarea>
                    <button class="btn btn-secondary">Actualizar consulta</button>
                </form>
            </article>
        </section>
    `,
};
