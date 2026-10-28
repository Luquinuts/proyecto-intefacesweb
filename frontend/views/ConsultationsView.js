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
                this.message = 'Consultation sent.';
                this.loadConsultations();
            } else this.message = result.mensaje || 'Could not send consultation.';
        },
        async updateConsultation(event) {
            event.preventDefault();
            const response = await fetch(`../backend/api/consultations.php/${this.selected.id}`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ status: this.updateStatus, professional_note: this.professionalNote }),
            });
            const result = await response.json();
            this.message = result.correcto ? 'Consultation updated.' : (result.mensaje || 'Could not update consultation.');
            if (result.correcto) {
                await this.loadConsultations();
                this.selected = this.consultations.find((consultation) => consultation.id === this.selected.id) || null;
            }
        },
    },
    template: `
        <section class="pasos-card">
            <header class="mb-4"><p class="brand fw-bold">PASOS</p><h1 class="h3">Your next reasonable step starts with understanding.</h1></header>
            <form id="consultation-form" class="card shadow-sm p-3 mb-4">
                <label class="form-label" for="description">What happened?</label>
                <textarea id="description" v-model.trim="description" class="form-control mb-3" rows="4" maxlength="3000" required></textarea>
                <label class="form-label" for="contact-preference">Preferred contact</label>
                <select id="contact-preference" v-model="contactPreference" class="form-select mb-3">
                    <option value="email">Email</option><option value="phone">Phone</option><option value="video">Video call</option>
                </select>
                <button class="btn btn-primary">Send consultation</button>
                <p v-if="message" class="alert alert-info mt-3 mb-0">{{ message }}</p>
            </form>
            <consultation-table :consultations="consultations" @select="selectConsultation"></consultation-table>
            <article v-if="selected" class="alert alert-secondary mt-3">
                <strong>Selected consultation:</strong> {{ selected.description }}<br>
                <span>Status: {{ selected.status }}</span>
                <form v-if="role === 'administrator' || role === 'professional'" id="consultation-update-form" class="mt-3" @submit="updateConsultation">
                    <label class="form-label" for="consultation-status">Status</label>
                    <select id="consultation-status" v-model="updateStatus" class="form-select mb-2">
                        <option value="received">Received</option><option value="under_review">Under review</option><option value="scheduled">Scheduled</option><option value="closed">Closed</option>
                    </select>
                    <label class="form-label" for="professional-note">Professional note</label>
                    <textarea id="professional-note" v-model.trim="professionalNote" class="form-control mb-2" rows="3" maxlength="3000"></textarea>
                    <button class="btn btn-secondary">Update consultation</button>
                </form>
            </article>
        </section>
    `,
};
