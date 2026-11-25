<script>
import $ from 'jquery';
import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';
import { apiUrl } from '../config.js';
import ConsultationTable from '../components/ConsultationTable.vue';

async function requestJson(url, options, fallbackMessage) {
    try {
        const response = await fetch(url, options);
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
    components: { ConsultationTable },
    props: { role: { type: String, default: 'user' } },
    data() {
        return {
            consultations: [],
            description: '',
            contactPreference: 'phone',
            phone: '',
            savedPhone: null,
            profileLoading: true,
            profileError: '',
            now: Date.now(),
            clockTimer: null,
            selected: null,
            updateStatus: 'received',
            professionalNote: '',
            message: '',
            scheduleDateTime: '',
            meetingLink: '',
            selectedDate: '',
            scheduleTime: '',
            timeSlots: ['09:00','09:30','10:00','10:30','11:00','11:30','12:00','12:30','13:00','13:30','14:00','14:30','15:00','15:30','16:00','16:30','17:00'],
            googleConnected: false,
            googleEmail: '',
            googleConfigured: true,
            googleStatusMessage: '',
            scheduling: false,
        };
    },
    mounted() {
        this.loadProfile();
        this.clockTimer = setInterval(() => { this.now = Date.now(); }, 1000);
        this.loadConsultations();
        this.loadGoogleStatus();
        this.handleGoogleCallback();
        $(this.$el).on('submit.pasosConsultations', '#consultation-form', (event) => this.submitConsultation(event));
        $(this.$el).on('submit.pasosConsultations', '#consultation-update-form', (event) => this.updateConsultation(event));
    },
    beforeUnmount() {
        $(this.$el).off('.pasosConsultations');
        clearInterval(this.clockTimer);
    },
    updated() {
        this.$nextTick(() => {
            if (this.role === 'professional' && (this.selected && (this.selected.contact_preference === 'phone' || this.selected.contact_preference === 'video'))) {
                const calEl = document.getElementById('schedule-calendar');
                if (calEl && !calEl.dataset.init) {
                    calEl.dataset.init = '1';
                    const calendar = new Calendar(calEl, {
                        plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
                        initialView: 'dayGridMonth',
                        locale: 'es',
                        height: 'auto',
                        headerToolbar: {
                            left: 'prev,next today',
                            center: 'title',
                            right: 'dayGridMonth,timeGridWeek,timeGridDay'
                        },
                                        dateClick: (info) => {
                            this.selectedDate = info.dateStr;
                            this.scheduleTime = '';
                            this.scheduleDateTime = '';
                            calEl.querySelectorAll('.fc-daygrid-day').forEach(d => d.style.backgroundColor = '');
                            info.dayEl.style.backgroundColor = '#BF9169';
                            info.dayEl.style.border = '2px solid #112620';
                        }
                    });
                    calendar.render();
                }
            }
        });
    },
    methods: {
        async loadProfile() {
            this.profileLoading = true;
            this.profileError = '';
            const result = await requestJson(apiUrl('sesion_actual.php'), undefined, 'No se pudo cargar tu teléfono guardado.');
            if (result.correcto) this.savedPhone = result.phone || null;
            else this.profileError = result.mensaje || 'No se pudo cargar tu teléfono guardado.';
            this.profileLoading = false;
        },
        logout() {
            fetch(apiUrl('cerrar_sesion.php'), { method: 'POST' })
                .finally(() => {
                    window.location.hash = '';
                    window.location.reload();
                });
        },
        async loadGoogleStatus() {
            if (this.role !== 'professional') return;
            const result = await requestJson(apiUrl('api/google_status.php'), undefined, 'No se pudo consultar Google Calendar.');
            if (result.correcto) {
                this.googleConnected = !!result.connected;
                this.googleEmail = result.google_email || '';
                this.googleConfigured = result.configured !== false;
            } else {
                this.googleConnected = false;
            }
        },
        handleGoogleCallback() {
            const hash = window.location.hash || '';
            const queryIndex = hash.indexOf('?');
            if (queryIndex === -1) return;
            const params = new URLSearchParams(hash.substring(queryIndex + 1));
            const google = params.get('google');
            if (!google) return;
            if (google === 'connected') {
                this.googleStatusMessage = 'Cuenta de Google conectada correctamente.';
                this.loadGoogleStatus();
            } else if (google === 'error') {
                this.googleStatusMessage = 'La conexión con Google fue cancelada o falló.';
            } else {
                this.googleStatusMessage = 'No se pudo completar la conexión con Google.';
            }
            window.location.hash = '#consultations';
        },
        connectGoogle() {
            window.location.href = apiUrl('api/google_auth.php');
        },
        async loadConsultations() {
            const result = await requestJson(apiUrl('api/consultations.php'), undefined, 'No se pudieron cargar las consultas.');
            if (result.correcto) {
                this.consultations = result.consultations;
                if (this.selected) this.selected = this.consultations.find((consultation) => consultation.id === this.selected.id) || null;
            }
            else this.message = result.mensaje;
        },
        selectConsultation(consultation) {
            this.selected = consultation;
            this.updateStatus = consultation.status;
            this.professionalNote = consultation.professional_note || '';
            this.scheduleDateTime = consultation.scheduled_at ? consultation.scheduled_at.replace(' ', 'T').substring(0, 16) : '';
            if (this.scheduleDateTime) {
                this.selectedDate = this.scheduleDateTime.split('T')[0];
                this.scheduleTime = this.scheduleDateTime.split('T')[1];
            } else {
                this.selectedDate = '';
                this.scheduleTime = '';
            }
            this.meetingLink = consultation.meeting_link || '';
        },
        async submitConsultation(event) {
            event.preventDefault();
            if (this.profileLoading || this.profileError) return;
            this.message = '';
            const payload = { description: this.description, contact_preference: this.contactPreference };
            if (this.contactPreference === 'phone' && !this.savedPhone) payload.phone = this.phone;
            const result = await requestJson(apiUrl('api/consultations.php'), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload),
            }, 'No se pudo enviar la consulta.');
            if (result.correcto) {
                this.description = '';
                this.message = 'Consulta enviada.';
                await this.loadProfile();
                this.phone = '';
                this.loadConsultations();
            } else this.message = result.mensaje || 'No se pudo enviar la consulta.';
        },
        async updateConsultation(event) {
            event.preventDefault();
            const result = await requestJson(apiUrl(`api/consultations.php/${this.selected.id}`), {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ status: this.updateStatus, professional_note: this.professionalNote }),
            }, 'No se pudo actualizar la consulta.');
            this.message = result.correcto ? 'Consulta actualizada.' : (result.mensaje || 'No se pudo actualizar la consulta.');
            if (result.correcto) {
                await this.loadConsultations();
            }
        },
        selectTime(slot) {
            if (this.isSlotBusy(slot)) return;
            this.scheduleTime = slot;
            this.scheduleDateTime = `${this.selectedDate}T${slot}`;
        },
        updateDateTime() {
            if (this.selectedDate && this.scheduleTime) {
                this.scheduleDateTime = `${this.selectedDate}T${this.scheduleTime}`;
            }
        },
        async scheduleConsultation() {
            if (this.scheduling) return;
            if (!this.scheduleDateTime) {
                this.message = 'Selecciona fecha y hora.';
                return;
            }
            if (!this.googleConnected) {
                this.message = 'Conectá tu cuenta de Google antes de agendar.';
                return;
            }
            const payload = { scheduled_at: this.scheduleDateTime };
            const selectedId = this.selected.id;
            this.scheduling = true;
            try {
                const result = await requestJson(apiUrl(`api/consultations.php/${selectedId}`), {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload),
                }, 'No se pudo agendar la cita. Reintenta con la misma fecha para comprobar el evento.');
                this.message = result.correcto ? 'Cita agendada y sincronizada con Google Calendar.' : (result.mensaje || 'No se pudo agendar la cita.');
                if (result.correcto && result.conference_status === 'pending') this.message = 'Cita guardada. Google está preparando el enlace; usa Comprobar enlace para volver a consultar.';
                if (result.correcto && result.conference_status === 'failure') this.message = 'Cita guardada, pero Google no pudo generar la conferencia. Revisa el evento en Calendar; no se creará otro evento al comprobar.';
                if (result.correcto) {
                    await this.loadConsultations();
                }
            } finally {
                this.scheduling = false;
            }
        },
        async markClosed() {
            const result = await requestJson(apiUrl(`api/consultations.php/${this.selected.id}`), {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ status: 'closed' }),
            }, 'No se pudo cerrar la consulta.');
            this.message = result.correcto ? 'Consulta cerrada.' : (result.mensaje || 'No se pudo cerrar la consulta.');
            if (result.correcto) {
                await this.loadConsultations();
            }
        },
        isMeetingAvailable(consultation) {
            return !!consultation?.can_join && this.now < Date.parse(consultation.ends_at);
        },
        meetingLinkFor(consultation) {
            return consultation ? apiUrl(`api/join_meeting.php?id=${consultation.id}`) : '';
        },
        meetingLabel(consultation) {
            if (consultation.status === 'closed' || this.now >= Date.parse(consultation.ends_at)) return 'Videollamada finalizada';
            if (this.now < Date.parse(consultation.scheduled_at_iso)) return 'Disponible en la fecha y hora agendada (Buenos Aires)';
            return this.isMeetingAvailable(consultation) ? 'Unirse a Google Meet' : 'Actualizá las consultas para comprobar el acceso';
        },
        isSlotBusy(slot) {
            const start = Date.parse(`${this.selectedDate}T${slot}:00-03:00`);
            return this.consultations.some((item) => item.id !== this.selected?.id && item.status === 'scheduled'
                && ['phone', 'video'].includes(item.contact_preference)
                && Date.parse(item.scheduled_at_iso) < start + 1800000 && Date.parse(item.ends_at) > start);
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
                phone: 'Teléfono',
                video: 'Videollamada',
            };
            return labels[pref] || pref;
        },
    },
};
</script>

<template>
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
                    <option value="phone">Teléfono</option><option value="video">Videollamada</option>
                </select>
                <p v-if="profileLoading" role="status">Cargando datos de contacto…</p>
                <div v-else-if="profileError" class="alert alert-warning" role="alert">
                    {{ profileError }} <button type="button" class="btn btn-outline-secondary btn-sm" @click="loadProfile">Reintentar</button>
                </div>
                <div v-else-if="contactPreference === 'phone' && !savedPhone" class="mb-3">
                    <label class="form-label" for="contact-phone">Teléfono internacional</label>
                    <input id="contact-phone" v-model="phone" type="tel" autocomplete="tel" class="form-control" maxlength="40" aria-describedby="phone-help" required>
                    <div id="phone-help" class="form-text">Incluí + y código de país, por ejemplo +54 9 342 1234567. Se guarda una sola vez.</div>
                </div>
                <p v-else-if="contactPreference === 'phone'" class="form-text text-white">Usaremos tu teléfono guardado: {{ savedPhone }}</p>
                <button class="btn btn-primary" :disabled="profileLoading || !!profileError">Enviar consulta</button>
                <p v-if="message" class="alert alert-info mt-3 mb-0">{{ message }}</p>
            </form>
            <button type="button" class="btn btn-outline-secondary btn-sm mb-2" @click="loadConsultations">Actualizar consultas</button>
            <consultation-table :consultations="consultations" @select="selectConsultation"></consultation-table>
            <article v-if="selected" class="alert alert-secondary mt-3">
                <strong>Consulta seleccionada:</strong> {{ selected.description }}<br>
                <span v-if="!(role === 'professional' && (selected.contact_preference === 'phone' || selected.contact_preference === 'video'))">Preferencia de contacto: {{ getContactLabel(selected.contact_preference) }}</span><br v-if="!(role === 'professional' && (selected.contact_preference === 'phone' || selected.contact_preference === 'video'))">
                <span v-if="!(role === 'professional' && (selected.contact_preference === 'phone' || selected.contact_preference === 'video'))">Estado: {{ getStatusLabel(selected.status) }}</span><br v-if="!(role === 'professional' && (selected.contact_preference === 'phone' || selected.contact_preference === 'video'))">
                <span v-if="selected.scheduled_at && !(role === 'professional' && (selected.contact_preference === 'phone' || selected.contact_preference === 'video'))">Fecha/hora: {{ selected.scheduled_at }}</span><br v-if="selected.scheduled_at && !(role === 'professional' && (selected.contact_preference === 'phone' || selected.contact_preference === 'video'))">
                <div v-if="selected.contact_preference === 'video' && selected.scheduled_at" class="my-2">
                    <a v-if="isMeetingAvailable(selected)" class="btn btn-primary btn-sm" :href="meetingLinkFor(selected)" target="_blank" rel="noopener noreferrer">Unirse a Google Meet</a>
                    <span v-else :class="meetingLabel(selected) === 'Videollamada finalizada' ? 'text-white' : 'text-muted'" role="status">{{ meetingLabel(selected) }}</span>
                </div>
                <div v-if="role === 'professional' && (selected.contact_preference === 'phone' || selected.contact_preference === 'video')" class="mt-3">
                    <label class="form-label">Agendar cita con calendario</label>
                    <div class="mb-2 p-2 border rounded" style="background:#fff;">
                        <div v-if="googleStatusMessage" class="alert alert-info py-2 mb-2">{{ googleStatusMessage }}</div>
                        <div v-if="!googleConfigured" class="alert alert-warning py-2 mb-2">
                            Google no está configurado. Completá <code>backend/config/google.local.php</code> con tus credenciales.
                        </div>
                        <div v-if="googleConnected" class="text-success mb-2">
                            Google Calendar conectado<span v-if="googleEmail">: {{ googleEmail }}</span>.
                        </div>
                        <div v-else class="mb-2">
                            <button type="button" class="btn btn-outline-primary btn-sm" @click="connectGoogle" :disabled="!googleConfigured">Conectar Google Calendar</button>
                        </div>
                    </div>
                    <div id="schedule-calendar" class="mb-2"></div>
                    <div v-if="selectedDate" class="mt-3 p-3 border rounded" style="background: #112620; color: #D9D1C7;">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <strong>Horarios disponibles - {{ selectedDate }}</strong>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <button v-for="slot in timeSlots" :key="slot" type="button" class="btn btn-sm" :class="scheduleTime === slot ? 'btn-primary' : 'btn-outline-secondary'" :disabled="isSlotBusy(slot)" :aria-label="slot + (isSlotBusy(slot) ? ' ocupado' : ' disponible')" @click="selectTime(slot)">{{ slot }}{{ isSlotBusy(slot) ? ' (ocupado)' : '' }}</button>
                        </div>
                    </div>
                    <div v-if="selected.contact_preference === 'video'" class="form-text mb-2">El enlace de Google Meet se generará automáticamente al agendar.</div>
                    <button class="btn btn-secondary btn-sm" @click="scheduleConsultation" :disabled="!googleConnected || scheduling">{{ scheduling ? 'Consultando…' : (selected.contact_preference === 'video' && selected.scheduled_at && !selected.meeting_link ? 'Comprobar enlace / reagendar' : 'Agendar') }}</button>
                </div>
                <form v-if="(role === 'administrator' || role === 'professional') && !(role === 'professional' && (selected.contact_preference === 'phone' || selected.contact_preference === 'video'))" id="consultation-update-form" class="mt-3">
                    <label class="form-label" for="consultation-status">Estado</label>
                    <select id="consultation-status" v-model="updateStatus" class="form-select mb-2">
                        <option v-if="selected.contact_preference !== 'phone' && selected.contact_preference !== 'video'" value="received">Recibida</option>
                        <option v-if="selected.contact_preference !== 'phone' && selected.contact_preference !== 'video'" value="under_review">En revisión</option>
                        <option v-if="selected.scheduled_at || (selected.contact_preference !== 'phone' && selected.contact_preference !== 'video')" value="scheduled">Agendada</option>
                        <option v-if="(selected.contact_preference === 'phone' || selected.contact_preference === 'video') && selected.scheduled_at" value="closed">Cerrada</option>
                        <option v-if="selected.contact_preference !== 'phone' && selected.contact_preference !== 'video'" value="closed">Cerrada</option>
                    </select>
                    <label class="form-label" for="professional-note">Nota del profesional</label>
                    <textarea id="professional-note" v-model.trim="professionalNote" class="form-control mb-2" rows="3" maxlength="3000"></textarea>
                    <button class="btn btn-secondary">Actualizar consulta</button>
                </form>
                <div v-if="role === 'professional' && (selected.contact_preference === 'phone' || selected.contact_preference === 'video') && selected.scheduled_at" class="mt-3">
                    <button class="btn btn-secondary btn-sm" @click="markClosed">Marcar como cerrada</button>
                </div>
            </article>
            <p v-if="role === 'professional' && message" class="alert alert-info mt-3" role="status">{{ message }}</p>
        </section>
</template>
