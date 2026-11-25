<script>
import $ from 'jquery';

export default {
    props: { consultations: { type: Array, default: () => [] } },
    emits: ['select'],
    mounted() {
        $(this.$el).on('input.pasosTable', '#consultation-filter', (event) => {
            const text = event.target.value.toLowerCase();
            $(this.$el).find('tbody tr').each(function () {
                $(this).toggle($(this).text().toLowerCase().includes(text));
            });
        });
        $(this.$el).on('click.pasosTable keydown.pasosTable', 'tbody tr[data-id]', (event) => {
            if (event.type === 'keydown' && event.key !== 'Enter' && event.key !== ' ') return;
            if (event.type === 'keydown') event.preventDefault();
            const consultation = this.consultations.find((item) => String(item.id) === event.currentTarget.dataset.id);
            if (consultation) this.selectConsultation(consultation);
        });
    },
    beforeUnmount() { $(this.$el).off('.pasosTable'); },
    methods: {
        selectConsultation(consultation) { this.$emit('select', consultation); },
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
};
</script>

<template>
        <section class="card shadow-sm p-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h2 class="h5 mb-0">Consultas</h2>
                <input id="consultation-filter" class="form-control form-control-sm w-50" placeholder="Filtrar">
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead><tr><th scope="col">Descripción</th><th scope="col">Preferencia de contacto</th><th scope="col">Teléfono</th><th scope="col">Estado</th><th scope="col">Fecha</th></tr></thead>
                    <tbody>
                        <tr v-for="consultation in consultations" :key="consultation.id" :data-id="consultation.id" tabindex="0" role="button" aria-label="Seleccionar consulta">
                            <td>{{ consultation.description }}</td>
                            <td>{{ getContactLabel(consultation.contact_preference) }}</td>
                            <td>{{ consultation.phone || '—' }}</td>
                            <td>{{ getStatusLabel(consultation.status) }}</td>
                            <td>{{ consultation.requested_at }}</td>
                        </tr>
                        <tr v-if="!consultations.length"><td colspan="5">Aún no hay consultas.</td></tr>
                    </tbody>
                </table>
            </div>
        </section>
</template>
