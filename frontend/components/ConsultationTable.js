export default {
    props: { consultations: { type: Array, default: () => [] } },
    emits: ['select'],
    mounted() {
        $(this.$el).on('input', '#consultation-filter', (event) => {
            const text = event.target.value.toLowerCase();
            $(this.$el).find('tbody tr').each(function () {
                $(this).toggle($(this).text().toLowerCase().includes(text));
            });
        });
    },
    beforeUnmount() { $(this.$el).off('input', '#consultation-filter'); },
    methods: {
        selectConsultation(consultation) { this.$emit('select', consultation); },
    },
    template: `
        <section class="card shadow-sm p-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h2 class="h5 mb-0">Your consultations</h2>
                <input id="consultation-filter" class="form-control form-control-sm w-50" placeholder="Filter">
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead><tr><th>Description</th><th>Status</th><th>Date</th></tr></thead>
                    <tbody>
                        <tr v-for="consultation in consultations" :key="consultation.id" @click="selectConsultation(consultation)">
                            <td>{{ consultation.description }}</td><td>{{ consultation.status }}</td><td>{{ consultation.requested_at }}</td>
                        </tr>
                        <tr v-if="!consultations.length"><td colspan="3">No consultations yet.</td></tr>
                    </tbody>
                </table>
            </div>
        </section>
    `,
};
