import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import FloatingInput from '@/Components/FloatingInput';
import FloatingSelect from '@/Components/FloatingSelect';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import Spinner from '@/Components/Spinner';
import { Head, Link, useForm } from '@inertiajs/react';
import { useEffect, useState } from 'react';

export default function JumuiyasCreate({ zones }) {
    const [rows, setRows] = useState([
        { name: '', meeting_day: '', established_year: '' },
    ]);

    const { data, setData, post, processing, errors, reset } = useForm({
        zone_uuid: '',
        jumuiyas: rows,
    });

    useEffect(() => {
        setData('jumuiyas', rows);
    }, [rows]);

    const addRow = () => {
        setRows([
            ...rows,
            { name: '', meeting_day: '', established_year: '' },
        ]);
    };

    const removeRow = (index) => {
        const next = rows.filter((_, i) => i !== index);
        setRows(
            next.length
                ? next
                : [{ name: '', meeting_day: '', established_year: '' }],
        );
    };

    const updateRow = (index, key, value) => {
        setRows(rows.map((r, i) => (i === index ? { ...r, [key]: value } : r)));
    };

    const submit = (e) => {
        e.preventDefault();
        post(route('jumuiyas.store'), {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                const initial = [
                    { name: '', meeting_day: '', established_year: '' },
                ];
                setRows(initial);
                setData('jumuiyas', initial);
                setData('zone_uuid', '');
            },
        });
    };

    return (
        <AuthenticatedLayout>
            <Head title="+ Christian Communities" />

            <div className="mx-auto max-w-6xl space-y-6">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h1 className="text-xl font-semibold text-slate-900">+ Christian Communities</h1>
                        <p className="mt-1 text-sm text-slate-500">Add one or many Christian communities at once.</p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Link
                            href={route('jumuiyas.index')}
                            className="inline-flex h-11 items-center rounded-lg border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                        >
                            Back to list
                        </Link>
                    </div>
                </div>

                <section className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
                    <form onSubmit={submit} className="space-y-4">
                        {Object.keys(errors ?? {}).length > 0 && (
                            <div className="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                                Fix the highlighted errors below. Bulk save is processed together, so nothing is saved until all rows are valid.
                            </div>
                        )}

                        <FloatingSelect
                            id="jumuiyas_zone_uuid"
                            label="Zone"
                            required
                            value={data.zone_uuid}
                            onChange={(e) => setData('zone_uuid', e.target.value)}
                            error={errors.zone_uuid}
                        >
                            <option value="">Select zone</option>
                            {(zones ?? []).map((z) => (
                                <option key={z.uuid} value={z.uuid}>{z.name}</option>
                            ))}
                        </FloatingSelect>

                        {rows.map((row, idx) => (
                            <div key={idx} className="rounded-xl border border-slate-200 p-4">
                                <div className="grid gap-4 md:grid-cols-2">
                                    <FloatingInput
                                        id={`jumuiya_name_${idx}`}
                                        label="Community name"
                                        required
                                        value={row.name}
                                        onChange={(e) => updateRow(idx, 'name', e.target.value)}
                                        error={errors[`jumuiyas.${idx}.name`]}
                                    />
                                    <FloatingInput
                                        id={`jumuiya_day_${idx}`}
                                        label="Meeting day"
                                        value={row.meeting_day}
                                        onChange={(e) => updateRow(idx, 'meeting_day', e.target.value)}
                                        error={errors[`jumuiyas.${idx}.meeting_day`]}
                                    />
                                    <FloatingInput
                                        id={`jumuiya_year_${idx}`}
                                        label="Established year"
                                        type="number"
                                        min={1800}
                                        max={2100}
                                        value={row.established_year}
                                        onChange={(e) => updateRow(idx, 'established_year', e.target.value)}
                                        error={errors[`jumuiyas.${idx}.established_year`]}
                                    />
                                </div>

                                <div className="mt-4 flex items-center justify-between">
                                    <button
                                        type="button"
                                        onClick={() => removeRow(idx)}
                                        className="text-sm font-semibold text-rose-700 hover:text-rose-800"
                                    >
                                        Remove
                                    </button>
                                </div>
                            </div>
                        ))}

                        <div className="flex flex-wrap items-center justify-between gap-2">
                            <SecondaryButton
                                type="button"
                                onClick={addRow}
                                className="h-11 rounded-lg text-sm font-semibold normal-case tracking-normal"
                            >
                                Add another
                            </SecondaryButton>

                            <PrimaryButton
                                disabled={processing}
                                className="h-11 gap-2 rounded-lg text-sm font-semibold normal-case tracking-normal bg-indigo-600 text-white hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-800"
                            >
                                {processing && <Spinner size="sm" className="text-white" />}
                                <span>Save Christian Communities</span>
                            </PrimaryButton>
                        </div>
                    </form>
                </section>
            </div>
        </AuthenticatedLayout>
    );
}
