import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import FloatingInput from '@/Components/FloatingInput';
import ModalHeader from '@/Components/ModalHeader';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import SearchableMemberSelect from '@/Components/SearchableMemberSelect';
import Spinner from '@/Components/Spinner';
import { Head, useForm } from '@inertiajs/react';

export default function DeceasedRegisterForm({ mode = 'create', title, subtitle, submitLabel, submitRoute, method = 'post', initial }) {
    const form = useForm({
        member_uuid: initial?.member_uuid ?? '',
        date_of_death: initial?.date_of_death ?? '',
        time_of_death: initial?.time_of_death ? String(initial.time_of_death).slice(0, 5) : '',
        place_of_death: initial?.place_of_death ?? '',
        cause_of_death: initial?.cause_of_death ?? '',
        death_certificate_number: initial?.death_certificate_number ?? '',
        hospital_or_health_facility: initial?.hospital_or_health_facility ?? '',
        funeral_date: initial?.funeral_date ?? '',
        burial_date: initial?.burial_date ?? '',
        burial_location_or_cemetery: initial?.burial_location_or_cemetery ?? '',
        funeral_mass_location: initial?.funeral_mass_location ?? '',
        priest_or_celebrant_name: initial?.priest_or_celebrant_name ?? '',
        homily_or_remarks: initial?.homily_or_remarks ?? '',
        notes: initial?.notes ?? '',
    });

    const submit = (event) => {
        event.preventDefault();

        const options = {
            preserveScroll: true,
        };

        if (method === 'patch') {
            form.patch(submitRoute, options);
            return;
        }

        form.post(submitRoute, options);
    };

    return (
        <AuthenticatedLayout>
            <Head title={title} />

            <div className="mx-auto max-w-6xl space-y-6">
                <section className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
                    <ModalHeader
                        title={title}
                        subtitle={subtitle}
                        onClose={() => window.history.back()}
                        showRequiredNote
                    />

                    <form onSubmit={submit} className="mt-4 space-y-6">
                        <div className="grid gap-4 md:grid-cols-2">
                            <SearchableMemberSelect
                                id="deceased_member"
                                label="Member"
                                value={form.data.member_uuid}
                                onChange={(value) => form.setData('member_uuid', value)}
                                disabled={mode === 'edit'}
                                error={form.errors.member_uuid}
                            />
                            <FloatingInput id="date_of_death" type="date" label="Date of death" required value={form.data.date_of_death} onChange={(e) => form.setData('date_of_death', e.target.value)} error={form.errors.date_of_death} />
                            <FloatingInput id="time_of_death" type="time" label="Time of death" value={form.data.time_of_death} onChange={(e) => form.setData('time_of_death', e.target.value)} error={form.errors.time_of_death} />
                            <FloatingInput id="place_of_death" label="Place of death" required value={form.data.place_of_death} onChange={(e) => form.setData('place_of_death', e.target.value)} error={form.errors.place_of_death} />
                            <FloatingInput id="cause_of_death" label="Cause of death" value={form.data.cause_of_death} onChange={(e) => form.setData('cause_of_death', e.target.value)} error={form.errors.cause_of_death} />
                            <FloatingInput id="death_certificate_number" label="Death certificate number" value={form.data.death_certificate_number} onChange={(e) => form.setData('death_certificate_number', e.target.value)} error={form.errors.death_certificate_number} />
                            <FloatingInput id="hospital_or_health_facility" label="Hospital / health facility" value={form.data.hospital_or_health_facility} onChange={(e) => form.setData('hospital_or_health_facility', e.target.value)} error={form.errors.hospital_or_health_facility} />
                            <FloatingInput id="funeral_date" type="date" label="Funeral date" value={form.data.funeral_date} onChange={(e) => form.setData('funeral_date', e.target.value)} error={form.errors.funeral_date} />
                            <FloatingInput id="burial_date" type="date" label="Burial date" value={form.data.burial_date} onChange={(e) => form.setData('burial_date', e.target.value)} error={form.errors.burial_date} />
                            <FloatingInput id="burial_location_or_cemetery" label="Burial location / cemetery" value={form.data.burial_location_or_cemetery} onChange={(e) => form.setData('burial_location_or_cemetery', e.target.value)} error={form.errors.burial_location_or_cemetery} />
                            <FloatingInput id="funeral_mass_location" label="Funeral mass location" value={form.data.funeral_mass_location} onChange={(e) => form.setData('funeral_mass_location', e.target.value)} error={form.errors.funeral_mass_location} />
                            <FloatingInput id="priest_or_celebrant_name" label="Priest / celebrant name" value={form.data.priest_or_celebrant_name} onChange={(e) => form.setData('priest_or_celebrant_name', e.target.value)} error={form.errors.priest_or_celebrant_name} />
                            <FloatingInput id="homily_or_remarks" label="Homily / remarks" value={form.data.homily_or_remarks} onChange={(e) => form.setData('homily_or_remarks', e.target.value)} error={form.errors.homily_or_remarks} className="md:col-span-2" />
                            <FloatingInput id="deceased_notes" label="Notes" value={form.data.notes} onChange={(e) => form.setData('notes', e.target.value)} error={form.errors.notes} className="md:col-span-2" />
                        </div>

                        <div className="flex justify-end gap-2">
                            <SecondaryButton type="button" onClick={() => window.history.back()} disabled={form.processing} className="h-11 rounded-lg text-sm font-semibold normal-case">Cancel</SecondaryButton>
                            <PrimaryButton disabled={form.processing} className="h-11 rounded-lg bg-indigo-600 px-4 text-sm font-semibold normal-case text-white hover:bg-indigo-700">
                                {form.processing && <Spinner size="sm" className="text-white" />}
                                <span>{submitLabel}</span>
                            </PrimaryButton>
                        </div>
                    </form>
                </section>
            </div>
        </AuthenticatedLayout>
    );
}
