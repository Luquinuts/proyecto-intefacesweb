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
        };
    },
    mounted() {
        this.loadConsultations();
        this.submitHandler = (event) => this.submitConsultation(event);
        $('#consultation-form').on('submit', this.submitHandler);
    },
    beforeUnmount() { $('#consultation-form').off('submit', this.submitHandler); },
    methods: {
        async loadConsultations() {
            const response = await fetch('../backend/api/consultations.php');
            const result = await response.json();
            if (result.correcto) this.consultations = result.consultations;
        },
        selectConsultation(consultation) {
            this.selected = consultation;
            this.updateStatus = consultation.status;
            this.professionalNote = consultation.professional_note || '';
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
        getStatusLabel(status) {
            const labels = {
                received: 'Recibida',
                under_review: 'En revisión',
                scheduled: 'Agendada',
                closed: 'Cerrada',
            };
            return labels[status] || status;
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
                <span>Estado: {{ getStatusLabel(selected.status) }}</span>
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
