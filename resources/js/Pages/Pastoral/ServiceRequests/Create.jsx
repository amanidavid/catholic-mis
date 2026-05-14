import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import FloatingInput from '@/Components/FloatingInput';
import FloatingSelect from '@/Components/FloatingSelect';
import ModalHeader from '@/Components/ModalHeader';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import SearchableFamilySelect from '@/Components/SearchableFamilySelect';
import SearchableJumuiyaSelect from '@/Components/SearchableJumuiyaSelect';
import SearchableMemberSelect from '@/Components/SearchableMemberSelect';
import Spinner from '@/Components/Spinner';
import { Head, useForm } from '@inertiajs/react';
import { useMemo } from 'react';

export default function ServiceRequestCreate({ categories, defaults }) {
    const defaultJumuiya = defaults?.jumuiya_uuid && defaults?.jumuiya_name
        ? { uuid: defaults.jumuiya_uuid, name: defaults.jumuiya_name }
        : null;

    const isScoped = useMemo(() => !!defaultJumuiya, [defaultJumuiya]);
    const todayIso = defaults?.request_date ?? new Date().toISOString().slice(0, 10);

    const form = useForm({
        jumuiya_uuid: defaults?.jumuiya_uuid ?? '',
        request_date: defaults?.request_date ?? '',
        preferred_service_date: '',
        urgency: defaults?.urgency ?? 'normal',
        notes: '',
        families: [newFamilyRow()],
    });

    const updateFamily = (index, key, value) => {
        const next = [...form.data.families];
        next[index] = { ...next[index], [key]: value };
        form.setData('families', next);
    };

    const addFamily = () => form.setData('families', [...form.data.families, newFamilyRow()]);
    const removeFamily = (index) => form.setData('families', form.data.families.filter((_, i) => i !== index));

    const addItem = (familyIndex) => {
        const next = [...form.data.families];
        next[familyIndex].items = [...next[familyIndex].items, newItemRow()];
        form.setData('families', next);
    };

    const updateItem = (familyIndex, itemIndex, key, value) => {
        const next = [...form.data.families];
        next[familyIndex].items[itemIndex] = { ...next[familyIndex].items[itemIndex], [key]: value };
        form.setData('families', next);
    };

    const removeItem = (familyIndex, itemIndex) => {
        const next = [...form.data.families];
        next[familyIndex].items = next[familyIndex].items.filter((_, i) => i !== itemIndex);
        if (next[familyIndex].items.length === 0) {
            next[familyIndex].items = [newItemRow()];
        }
        form.setData('families', next);
    };

    const submit = (event) => {
        event.preventDefault();

        const clientErrors = {};

        if (!isValidDateInput(form.data.request_date)) {
            clientErrors.request_date = 'Request date must be a valid date. Past, present, and future dates are allowed.';
        }

        if (form.data.preferred_service_date && !isTodayOrFutureDate(form.data.preferred_service_date, todayIso)) {
            clientErrors.preferred_service_date = 'Preferred service date must be today or a future date.';
        }

        form.data.families.forEach((family, familyIndex) => {
            family.items.forEach((item, itemIndex) => {
                if (item.requested_for_date && !isTodayOrFutureDate(item.requested_for_date, todayIso)) {
                    clientErrors[`families.${familyIndex}.items.${itemIndex}.requested_for_date`] = 'Requested-for date must be today or a future date.';
                }
            });
        });

        form.clearErrors();
        if (Object.keys(clientErrors).length > 0) {
            form.setError(clientErrors);
            return;
        }

        form.post(route('pastoral.service-requests.store'));
    };

    return (
        <AuthenticatedLayout>
            <Head title="New Service Request" />

            <div className="mx-auto max-w-7xl space-y-6">
                <section className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
                    <ModalHeader
                        title="New Service Request"
                        subtitle="Create a Jumuiya service request with one or more families and service items."
                        onClose={() => window.history.back()}
                        showRequiredNote
                    />

                    <form onSubmit={submit} className="mt-4 space-y-6">
                        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                            {isScoped ? (
                                <div className="lg:col-span-2 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                                    <div className="text-xs font-semibold uppercase tracking-wide text-slate-500">Christian Community</div>
                                    <div className="mt-1 text-sm font-semibold text-slate-900 break-words">{defaults?.jumuiya_name || '-'}</div>
                                </div>
                            ) : (
                                <SearchableJumuiyaSelect
                                    id="request_jumuiya"
                                    label="Christian Community"
                                    value={form.data.jumuiya_uuid}
                                    onChange={(value) => form.setData('jumuiya_uuid', value)}
                                    error={form.errors.jumuiya_uuid}
                                    className="lg:col-span-2"
                                />
                            )}
                            <FloatingInput
                                id="request_date"
                                type="date"
                                label="Request date"
                                required
                                value={form.data.request_date}
                                onChange={(e) => {
                                    form.setData('request_date', e.target.value);
                                    if (form.errors.request_date) {
                                        form.clearErrors('request_date');
                                    }
                                }}
                                error={form.errors.request_date}
                            />
                            <FloatingInput
                                id="preferred_service_date"
                                type="date"
                                label="Preferred service date"
                                min={todayIso}
                                value={form.data.preferred_service_date}
                                onChange={(e) => {
                                    form.setData('preferred_service_date', e.target.value);
                                    if (form.errors.preferred_service_date) {
                                        form.clearErrors('preferred_service_date');
                                    }
                                }}
                                error={form.errors.preferred_service_date}
                            />
                            <FloatingSelect id="request_urgency" label="Urgency" value={form.data.urgency} onChange={(e) => form.setData('urgency', e.target.value)} className="lg:col-span-1">
                                <option value="low">Low</option>
                                <option value="normal">Normal</option>
                                <option value="high">High</option>
                                <option value="urgent">Urgent</option>
                            </FloatingSelect>
                            <FloatingInput id="request_notes" label="Notes" value={form.data.notes} onChange={(e) => form.setData('notes', e.target.value)} error={form.errors.notes} className="lg:col-span-3" />
                        </div>

                        <section className="space-y-4 rounded-xl border border-slate-200 p-4">
                            <div className="flex items-center justify-between">
                                <h3 className="text-sm font-semibold text-slate-900">Families and Service Items</h3>
                                <button type="button" onClick={addFamily} className="rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-2 text-xs font-semibold text-indigo-700">Add Family</button>
                            </div>

                            {form.data.families.map((family, familyIndex) => (
                                <div key={family.local_id} className="space-y-3 rounded-lg border border-slate-200 p-3">
                                    <div className="grid gap-3 md:grid-cols-2">
                                        <SearchableFamilySelect
                                            id={`family_${family.local_id}`}
                                            label="Family"
                                            value={family.family_uuid}
                                            onChange={(value) => updateFamily(familyIndex, 'family_uuid', value)}
                                            jumuiyaUuid={form.data.jumuiya_uuid}
                                            disabled={!form.data.jumuiya_uuid}
                                            error={form.errors[`families.${familyIndex}.family_uuid`]}
                                        />
                                        <FloatingInput
                                            id={`family_notes_${family.local_id}`}
                                            label="Family notes"
                                            value={family.family_notes}
                                            onChange={(e) => updateFamily(familyIndex, 'family_notes', e.target.value)}
                                            error={form.errors[`families.${familyIndex}.family_notes`]}
                                        />
                                    </div>

                                    <div className="space-y-2">
                                        <div className="flex items-center justify-between">
                                            <p className="text-xs font-semibold text-slate-700">Service items</p>
                                        </div>

                                        <button type="button" onClick={() => addItem(familyIndex)} className="w-full rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-2 text-xs font-semibold text-indigo-700">Add Item</button>

                                        {family.items.map((item, itemIndex) => (
                                            <div key={item.local_id} className="grid gap-3 rounded-lg border border-slate-100 bg-slate-50 p-3 md:grid-cols-2 lg:grid-cols-4">
                                                <FloatingSelect
                                                    id={`item_category_${item.local_id}`}
                                                    label="Service category"
                                                    value={item.service_category_uuid}
                                                    onChange={(e) => updateItem(familyIndex, itemIndex, 'service_category_uuid', e.target.value)}
                                                >
                                                    <option value="">Select category</option>
                                                    {(categories?.data ?? []).map((category) => (
                                                        <option key={category.uuid} value={category.uuid}>{category.name}</option>
                                                    ))}
                                                </FloatingSelect>
                                                <SearchableMemberSelect
                                                    id={`target_member_${item.local_id}`}
                                                    label="Target member (optional)"
                                                    value={item.target_member_uuid}
                                                    onChange={(value) => updateItem(familyIndex, itemIndex, 'target_member_uuid', value)}
                                                    familyUuid={family.family_uuid}
                                                    disabled={!family.family_uuid}
                                                />
                                                <FloatingInput id={`item_desc_${item.local_id}`} label="Description" value={item.description} onChange={(e) => updateItem(familyIndex, itemIndex, 'description', e.target.value)} />
                                                <FloatingInput
                                                    id={`item_date_${item.local_id}`}
                                                    type="date"
                                                    label="Requested for date"
                                                    min={todayIso}
                                                    value={item.requested_for_date}
                                                    onChange={(e) => {
                                                        updateItem(familyIndex, itemIndex, 'requested_for_date', e.target.value);
                                                        const errorKey = `families.${familyIndex}.items.${itemIndex}.requested_for_date`;
                                                        if (form.errors[errorKey]) {
                                                            form.clearErrors(errorKey);
                                                        }
                                                    }}
                                                    error={form.errors[`families.${familyIndex}.items.${itemIndex}.requested_for_date`]}
                                                />
                                                <div className="md:col-span-2 lg:col-span-4 flex justify-end">
                                                    <button type="button" onClick={() => removeItem(familyIndex, itemIndex)} className="rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700">Remove Item</button>
                                                </div>
                                            </div>
                                        ))}
                                    </div>

                                    <div className="flex justify-end">
                                        {form.data.families.length > 1 && <button type="button" onClick={() => removeFamily(familyIndex)} className="rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700">Remove Family</button>}
                                    </div>
                                </div>
                            ))}
                        </section>

                        <div className="flex justify-end gap-2">
                            <SecondaryButton type="button" onClick={() => window.history.back()} className="h-11 rounded-lg text-sm font-semibold normal-case">Cancel</SecondaryButton>
                            <PrimaryButton disabled={form.processing} className="h-11 rounded-lg bg-indigo-600 px-5 text-sm font-semibold normal-case text-white hover:bg-indigo-700">
                                {form.processing && <Spinner size="sm" className="text-white" />}
                                <span>Save Request</span>
                            </PrimaryButton>
                        </div>
                    </form>
                </section>
            </div>
        </AuthenticatedLayout>
    );
}

function isValidDateInput(value) {
    if (typeof value !== 'string' || value.trim() === '') {
        return false;
    }

    const normalized = value.trim();
    const match = normalized.match(/^(\d{4})-(\d{2})-(\d{2})$/);
    if (!match) {
        return false;
    }

    const [, yearText, monthText, dayText] = match;
    const year = Number(yearText);
    const month = Number(monthText);
    const day = Number(dayText);

    if (!Number.isInteger(year) || !Number.isInteger(month) || !Number.isInteger(day)) {
        return false;
    }

    const date = new Date(year, month - 1, day);

    return date.getFullYear() === year
        && date.getMonth() === month - 1
        && date.getDate() === day;
}

function isTodayOrFutureDate(value, todayIso) {
    if (!isValidDateInput(value)) {
        return false;
    }

    return value >= todayIso;
}

function newFamilyRow() {
    return {
        local_id: `${Date.now()}_${Math.random().toString(36).slice(2, 8)}`,
        family_uuid: '',
        family_notes: '',
        items: [newItemRow()],
    };
}

function newItemRow() {
    return {
        local_id: `${Date.now()}_${Math.random().toString(36).slice(2, 8)}`,
        service_category_uuid: '',
        target_member_uuid: '',
        description: '',
        requested_for_date: '',
    };
}
