import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import ConfirmDialog from '@/Components/ConfirmDialog';
import FloatingInput from '@/Components/FloatingInput';
import Modal from '@/Components/Modal';
import ModalHeader from '@/Components/ModalHeader';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import Spinner from '@/Components/Spinner';
import { toTitleCase } from '@/lib/formatters';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';

export default function OutstationsIndex({ outstations, filters }) {
    const { auth } = usePage().props;

    const canManage = useMemo(() => {
        const permissions = auth?.user?.permissions ?? [];
        return Array.isArray(permissions) && (
            permissions.includes('outstations.create')
            || permissions.includes('outstations.update')
            || permissions.includes('outstations.delete')
        );
    }, [auth?.user?.permissions]);

    const [addOpen, setAddOpen] = useState(false);
    const [rows, setRows] = useState([{ name: '', description: '', established_year: '' }]);

    const { data, setData, post, processing, errors, reset } = useForm({
        outstations: rows,
    });

    const addRow = () => {
        const next = [...rows, { name: '', description: '', established_year: '' }];
        setRows(next);
        setData('outstations', next);
    };

    const removeRow = (index) => {
        const next = rows.filter((_, i) => i !== index);
        const fallback = next.length ? next : [{ name: '', description: '', established_year: '' }];
        setRows(fallback);
        setData('outstations', fallback);
    };

    const updateRow = (index, key, value) => {
        const next = rows.map((r, i) => (i === index ? { ...r, [key]: value } : r));
        setRows(next);
        setData('outstations', next);
    };

    const submitBulk = (e) => {
        e.preventDefault();
        post(route('outstations.store'), {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                const initial = [{ name: '', description: '', established_year: '' }];
                setRows(initial);
                setData('outstations', initial);
                setAddOpen(false);
            },
        });
    };

    const [q, setQ] = useState(filters?.q ?? '');

    const applySearch = (e) => {
        e.preventDefault();
        router.get(route('outstations.index'), { q }, { preserveState: true, replace: true });
    };

    const clearSearch = () => {
        setQ('');
        router.get(route('outstations.index'), {}, { preserveState: true, replace: true });
    };

    const [editOpen, setEditOpen] = useState(false);
    const [editingOutstation, setEditingOutstation] = useState(null);

    return (
        <AuthenticatedLayout>
            <Head title="Outstations" />

            <div className="mx-auto max-w-6xl space-y-6">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h1 className="text-xl font-semibold text-slate-900">Outstations</h1>
                        <p className="mt-1 text-sm text-slate-500">Manage parish outstations.</p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        {canManage && (
                            <PrimaryButton
                                type="button"
                                onClick={() => setAddOpen(true)}
                                className="h-11 gap-2 rounded-lg text-sm font-semibold normal-case tracking-normal bg-indigo-600 text-white hover:bg-indigo-700"
                            >
                                <span className="text-lg font-bold leading-none">+</span>
                                <span>Outstations</span>
                            </PrimaryButton>
                        )}
                        <Link
                            href={route('setup.index')}
                            className="inline-flex h-11 items-center rounded-lg border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                        >
                            Back to Setup
                        </Link>
                    </div>
                </div>

                <section className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
                    <form onSubmit={applySearch} className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                        <div className="w-full sm:max-w-md">
                            <FloatingInput
                                id="outstations_q"
                                label="Search (name)"
                                value={q}
                                onChange={(e) => setQ(e.target.value)}
                                hint=""
                            />
                        </div>
                        <div className="flex items-center gap-2">
                            <PrimaryButton type="submit" className="h-11 rounded-lg text-sm font-semibold normal-case tracking-normal bg-indigo-600 text-white hover:bg-indigo-700">
                                Search
                            </PrimaryButton>
                            <SecondaryButton type="button" onClick={clearSearch} className="h-11 rounded-lg text-sm font-semibold normal-case tracking-normal">
                                Clear
                            </SecondaryButton>
                        </div>
                    </form>

                    <div className="mt-6 overflow-x-auto">
                        <div className="overflow-hidden rounded-xl ring-1 ring-slate-200">
                            <table className="mis-table divide-y divide-slate-200">
                                <thead>
                                    <tr>
                                        <th className="w-16">#</th>
                                        <th>Name</th>
                                        <th>Year</th>
                                        <th>Status</th>
                                        {canManage && <th className="text-right">Actions</th>}
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100">
                                    {(outstations?.data ?? []).map((outstation, idx) => (
                                        <OutstationRow
                                            key={outstation.uuid}
                                            outstation={outstation}
                                            canManage={canManage}
                                            striped={idx % 2 === 1}
                                            index={(outstations?.meta?.from ?? 1) + idx}
                                            onEdit={() => {
                                                setEditingOutstation(outstation);
                                                setEditOpen(true);
                                            }}
                                        />
                                    ))}
                                    {(outstations?.data ?? []).length === 0 && (
                                        <tr>
                                            <td colSpan={canManage ? 5 : 4} className="px-4 py-10 text-center text-sm text-slate-500">
                                                No outstations found.
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div className="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <PaginationSummary meta={outstations?.meta} />
                        <Pagination links={outstations?.meta?.links ?? outstations?.links ?? []} />
                    </div>
                </section>
            </div>

            <Modal show={addOpen} onClose={() => setAddOpen(false)} maxWidth="2xl">
                <div className="p-6">
                    <ModalHeader title="Add outstations" subtitle="Add one or many outstations at once." onClose={() => setAddOpen(false)} showRequiredNote />

                    <form onSubmit={submitBulk} className="mt-4 space-y-4">
                        {Object.keys(errors ?? {}).length > 0 && (
                            <div className="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                                Fix the highlighted errors below. Bulk save is processed together, so nothing is saved until all rows are valid.
                            </div>
                        )}
                        {rows.map((row, idx) => (
                            <div key={idx} className="rounded-xl border border-slate-200 p-4">
                                <div className="grid gap-4 md:grid-cols-2">
                                    <FloatingInput
                                        id={`outstation_name_${idx}`}
                                        label="Outstation name"
                                        required
                                        value={row.name}
                                        onChange={(e) => updateRow(idx, 'name', e.target.value)}
                                        error={errors[`outstations.${idx}.name`]}
                                    />
                                    <FloatingInput
                                        id={`outstation_year_${idx}`}
                                        label="Established year"
                                        type="number"
                                        min={1800}
                                        max={2100}
                                        value={row.established_year}
                                        onChange={(e) => updateRow(idx, 'established_year', e.target.value)}
                                        error={errors[`outstations.${idx}.established_year`]}
                                    />
                                    <FloatingInput
                                        id={`outstation_desc_${idx}`}
                                        label="Description"
                                        value={row.description}
                                        onChange={(e) => updateRow(idx, 'description', e.target.value)}
                                        error={errors[`outstations.${idx}.description`]}
                                    />
                                </div>

                                <div className="mt-4 flex items-center justify-between">
                                    <button type="button" onClick={() => removeRow(idx)} className="text-sm font-semibold text-rose-700 hover:text-rose-800">
                                        Remove
                                    </button>
                                </div>
                            </div>
                        ))}

                        <div className="flex flex-wrap items-center justify-between gap-2">
                            <SecondaryButton type="button" onClick={addRow} className="h-11 rounded-lg text-sm font-semibold normal-case tracking-normal">
                                Add another
                            </SecondaryButton>
                            <PrimaryButton disabled={processing} className="h-11 gap-2 rounded-lg text-sm font-semibold normal-case tracking-normal bg-indigo-600 text-white hover:bg-indigo-700">
                                {processing && <Spinner size="sm" className="text-white" />}
                                <span>Save outstations</span>
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </Modal>

            <EditOutstationModal
                open={editOpen}
                onClose={() => {
                    setEditOpen(false);
                    setEditingOutstation(null);
                }}
                outstation={editingOutstation}
            />
        </AuthenticatedLayout>
    );
}

function OutstationRow({ outstation, canManage, striped = false, index, onEdit }) {
    const [confirmOpen, setConfirmOpen] = useState(false);

    const destroy = () => {
        router.delete(route('outstations.destroy', outstation.uuid), { preserveScroll: true });
    };

    return (
        <>
            <tr className={`${striped ? 'bg-slate-50/50' : 'bg-white'} hover:bg-indigo-50/40 transition`}>
                <td className="px-4 py-3 text-sm font-semibold text-slate-700">{index}</td>
                <td className="px-4 py-3 text-sm font-medium text-slate-900">{toTitleCase(outstation.name)}</td>
                <td className="px-4 py-3 text-sm text-slate-700">{outstation.established_year ?? '-'}</td>
                <td className="px-4 py-3 text-sm">
                    <span className={`inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ${outstation.is_active ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200' : 'bg-rose-50 text-rose-700 ring-1 ring-rose-200'}`}>
                        {outstation.is_active ? 'Active' : 'Inactive'}
                    </span>
                </td>
                {canManage && (
                    <td className="px-4 py-3">
                        <div className="flex items-center justify-end gap-2">
                            <button type="button" onClick={onEdit} className="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-indigo-200 bg-indigo-50 text-indigo-700 hover:bg-indigo-100" title="Edit">
                                <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M16.862 3.487a2.25 2.25 0 013.182 3.182L8.25 18.463 3 19.5l1.037-5.25L16.862 3.487z" />
                                </svg>
                            </button>
                            <button type="button" onClick={() => setConfirmOpen(true)} className="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-rose-200 bg-rose-50 text-rose-700 hover:bg-rose-100" title="Delete">
                                <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M6 7h12M9 7V5a1 1 0 011-1h4a1 1 0 011 1v2m-8 0l1 14h8l1-14" />
                                </svg>
                            </button>
                        </div>
                    </td>
                )}
            </tr>

            <ConfirmDialog
                open={confirmOpen}
                onClose={() => setConfirmOpen(false)}
                title="Delete outstation"
                message={`Are you sure you want to delete "${toTitleCase(outstation.name)}"?`}
                confirmText="Delete"
                onConfirm={destroy}
            />
        </>
    );
}

function Pagination({ links }) {
    if (!Array.isArray(links) || links.length === 0) {
        return null;
    }

    const isPrev = (label) => typeof label === 'string' && /previous/i.test(label);
    const isNext = (label) => typeof label === 'string' && /next/i.test(label);

    return (
        <div className="flex flex-wrap gap-2">
            {links.map((link, idx) => {
                const prev = isPrev(link.label);
                const next = isNext(link.label);
                const className = `inline-flex h-11 items-center justify-center rounded-lg px-4 text-sm font-semibold ${link.active ? 'bg-indigo-600 text-white' : link.url ? 'border border-slate-200 bg-white text-slate-700 hover:bg-slate-50' : 'cursor-not-allowed border border-slate-100 bg-slate-50 text-slate-400'}`;

                const content = prev ? (
                    <span className="inline-flex items-center gap-2">
                        <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" d="M15 19l-7-7 7-7" />
                        </svg>
                        <span>Previous</span>
                    </span>
                ) : next ? (
                    <span className="inline-flex items-center gap-2">
                        <span>Next</span>
                        <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" d="M9 5l7 7-7 7" />
                        </svg>
                    </span>
                ) : (
                    <span dangerouslySetInnerHTML={{ __html: link.label }} />
                );

                if (!link.url) {
                    return <span key={idx} className={className}>{content}</span>;
                }

                return <Link key={idx} href={link.url} preserveScroll className={className}>{content}</Link>;
            })}
        </div>
    );
}

function PaginationSummary({ meta }) {
    if (!meta || typeof meta !== 'object') {
        return null;
    }

    const from = meta.from ?? 0;
    const to = meta.to ?? 0;
    const total = meta.total ?? 0;

    if (!total) {
        return <div className="text-sm text-slate-500">Showing 0 results</div>;
    }

    return (
        <div className="text-sm text-slate-500">
            Showing <span className="font-semibold text-slate-700">{from}</span>-
            <span className="font-semibold text-slate-700">{to}</span> of{' '}
            <span className="font-semibold text-slate-700">{total}</span>
        </div>
    );
}

function EditOutstationModal({ open, onClose, outstation }) {
    const { data, setData, patch, processing, errors, clearErrors } = useForm({
        name: outstation?.name ?? '',
        description: outstation?.description ?? '',
        established_year: outstation?.established_year ?? '',
        is_active: outstation?.is_active ?? true,
    });

    useEffect(() => {
        if (!outstation || !open) return;

        clearErrors();
        setData({
            name: outstation?.name ?? '',
            description: outstation?.description ?? '',
            established_year: outstation?.established_year ?? '',
            is_active: outstation?.is_active ?? true,
        });
    }, [outstation?.uuid, open]);

    if (!outstation) {
        return null;
    }

    const submit = (e) => {
        e.preventDefault();
        patch(route('outstations.update', outstation.uuid), {
            preserveScroll: true,
            onSuccess: () => {
                clearErrors();
                onClose();
            },
        });
    };

    return (
        <Modal show={open} onClose={onClose} maxWidth="2xl">
            <div className="p-6">
                <ModalHeader title="Edit outstation" subtitle="Update outstation details." onClose={onClose} showRequiredNote />

                <form onSubmit={submit} className="mt-4 space-y-4">
                    <div className="grid gap-4 md:grid-cols-2">
                        <FloatingInput id={`edit_outstation_name_${outstation.uuid}`} label="Outstation name" required value={data.name} onChange={(e) => setData('name', e.target.value)} error={errors.name} />
                        <FloatingInput id={`edit_outstation_year_${outstation.uuid}`} label="Established year" type="number" min={1800} max={2100} value={data.established_year} onChange={(e) => setData('established_year', e.target.value)} error={errors.established_year} />
                        <FloatingInput id={`edit_outstation_desc_${outstation.uuid}`} label="Description" value={data.description} onChange={(e) => setData('description', e.target.value)} error={errors.description} />
                    </div>

                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <label className="flex items-center gap-2 text-sm font-semibold text-slate-700">
                            <input type="checkbox" checked={!!data.is_active} onChange={(e) => setData('is_active', e.target.checked)} className="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" />
                            Active
                        </label>

                        <div className="flex gap-2">
                            <SecondaryButton type="button" onClick={onClose} disabled={processing} className="h-11 rounded-lg text-sm font-semibold normal-case tracking-normal">
                                Cancel
                            </SecondaryButton>
                            <PrimaryButton disabled={processing} className="h-11 gap-2 rounded-lg text-sm font-semibold normal-case tracking-normal bg-indigo-600 text-white hover:bg-indigo-700">
                                {processing && <Spinner size="sm" className="text-white" />}
                                <span>Update</span>
                            </PrimaryButton>
                        </div>
                    </div>
                </form>
            </div>
        </Modal>
    );
}
