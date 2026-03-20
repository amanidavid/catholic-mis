import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import ConfirmDialog from '@/Components/ConfirmDialog';
import FloatingInput from '@/Components/FloatingInput';
import FloatingSelect from '@/Components/FloatingSelect';
import Modal from '@/Components/Modal';
import ModalHeader from '@/Components/ModalHeader';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import Spinner from '@/Components/Spinner';
import { toTitleCase } from '@/lib/formatters';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';

export default function JumuiyasIndex({ jumuiyas, zones, filters }) {
    const { auth } = usePage().props;

    const canManage = useMemo(() => {
        const permissions = auth?.user?.permissions ?? [];
        return Array.isArray(permissions) && (permissions.includes('jumuiyas.create') || permissions.includes('jumuiyas.update') || permissions.includes('jumuiyas.delete'));
    }, [auth?.user?.permissions]);

    const [q, setQ] = useState(filters?.q ?? '');
    const [zoneUuid, setZoneUuid] = useState(filters?.zone_uuid ?? '');

    const applySearch = (e) => {
        e.preventDefault();
        router.get(
            route('jumuiyas.index'),
            { q, zone_uuid: zoneUuid || undefined },
            { preserveState: true, replace: true },
        );
    };

    const clearSearch = () => {
        setQ('');
        setZoneUuid('');
        router.get(route('jumuiyas.index'), {}, { preserveState: true, replace: true });
    };

    const goToCreate = () => {
        router.visit(route('jumuiyas.create'));
    };

    const [editOpen, setEditOpen] = useState(false);
    const [editingJumuiya, setEditingJumuiya] = useState(null);

    const openEdit = (j) => {
        setEditingJumuiya(j);
        setEditOpen(true);
    };

    return (
        <AuthenticatedLayout>
            <Head title="Christian Communities" />

            <div className="mx-auto max-w-6xl space-y-6">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h1 className="text-xl font-semibold text-slate-900">Christian Communities</h1>
                        <p className="mt-1 text-sm text-slate-500">Manage Christian communities. Search and pagination are server-side.</p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        {canManage && (
                            <PrimaryButton
                                type="button"
                                onClick={goToCreate}
                                className="h-11 gap-2 rounded-lg text-sm font-semibold normal-case tracking-normal bg-indigo-600 text-white hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-800"
                            >
                                <span className="text-lg font-bold leading-none">+</span>
                                <span>Christian Communities</span>
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
                    <form onSubmit={applySearch} className="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                        <div className="grid w-full gap-3 sm:grid-cols-2 lg:max-w-2xl">
                            <FloatingInput
                                id="jumuiyas_q"
                                label="Search (name)"
                                value={q}
                                onChange={(e) => setQ(e.target.value)}
                                hint=""
                            />

                            <FloatingSelect
                                id="jumuiyas_zone_filter"
                                label="Zone"
                                value={zoneUuid}
                                onChange={(e) => setZoneUuid(e.target.value)}
                            >
                                <option value="">All zones</option>
                                {(zones ?? []).map((z) => (
                                    <option key={z.uuid} value={z.uuid}>{z.name}</option>
                                ))}
                            </FloatingSelect>
                        </div>

                        <div className="flex items-center gap-2">
                            <PrimaryButton
                                type="submit"
                                className="h-10 rounded-lg px-3 text-sm font-semibold normal-case tracking-normal bg-indigo-600 text-white hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-800"
                            >
                                Search
                            </PrimaryButton>
                            <SecondaryButton
                                type="button"
                                onClick={clearSearch}
                                className="h-10 rounded-lg px-3 text-sm font-semibold normal-case tracking-normal"
                            >
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
                                        <th>Zone</th>
                                        <th>Year</th>
                                        <th>Status</th>
                                        {canManage && <th className="text-right">Actions</th>}
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100">
                                    {(jumuiyas?.data ?? []).map((j, idx) => (
                                        <JumuiyaRow
                                            key={j.uuid}
                                            jumuiya={j}
                                            canManage={canManage}
                                            striped={idx % 2 === 1}
                                            index={(jumuiyas?.meta?.from ?? 1) + idx}
                                            onEdit={() => openEdit(j)}
                                        />
                                    ))}
                                    {(jumuiyas?.data ?? []).length === 0 && (
                                        <tr>
                                            <td colSpan={canManage ? 6 : 5} className="px-4 py-10 text-center text-sm text-slate-500">
                                                No Christian communities found.
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div className="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <PaginationSummary meta={jumuiyas?.meta} />
                        <Pagination links={jumuiyas?.meta?.links ?? jumuiyas?.links ?? []} />
                    </div>
                </section>
            </div>

            <EditJumuiyaModal
                open={editOpen}
                onClose={() => {
                    setEditOpen(false);
                    setEditingJumuiya(null);
                }}
                jumuiya={editingJumuiya}
                zones={zones}
            />
        </AuthenticatedLayout>
    );
}

function JumuiyaRow({ jumuiya, canManage, striped = false, index, onEdit }) {
    const [confirmOpen, setConfirmOpen] = useState(false);

    const destroy = () => {
        router.delete(route('jumuiyas.destroy', jumuiya.uuid), { preserveScroll: true });
    };

    return (
        <>
            <tr className={`${striped ? 'bg-slate-50/50' : 'bg-white'} hover:bg-indigo-50/40 transition`}>
                <td className="px-4 py-3 text-sm font-semibold text-slate-700">{index}</td>
                <td className="px-4 py-3 text-sm font-medium text-slate-900">{toTitleCase(jumuiya.name)}</td>
                <td className="px-4 py-3 text-sm text-slate-700">{toTitleCase(jumuiya.zone_name ?? '-')}</td>
                <td className="px-4 py-3 text-sm text-slate-700">{jumuiya.established_year ?? '-'}</td>
                <td className="px-4 py-3 text-sm">
                    <span
                        className={`inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ${jumuiya.is_active
                            ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200'
                            : 'bg-rose-50 text-rose-700 ring-1 ring-rose-200'
                            }`}
                    >
                        {jumuiya.is_active ? 'Active' : 'Inactive'}
                    </span>
                </td>
                {canManage && (
                    <td className="px-4 py-3">
                        <div className="flex items-center justify-end gap-2">
                            <button
                                type="button"
                                onClick={onEdit}
                                className="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-indigo-200 bg-indigo-50 text-indigo-700 hover:bg-indigo-100"
                                title="Edit"
                            >
                                <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M16.862 3.487a2.25 2.25 0 013.182 3.182L8.25 18.463 3 19.5l1.037-5.25L16.862 3.487z" />
                                </svg>
                            </button>
                            <button
                                type="button"
                                onClick={() => setConfirmOpen(true)}
                                className="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-rose-200 bg-rose-50 text-rose-700 hover:bg-rose-100"
                                title="Delete"
                            >
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
                title="Delete Christian Community"
                message={`Are you sure you want to delete "${toTitleCase(jumuiya.name)}"?`}
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

                const className = `inline-flex h-11 items-center justify-center rounded-lg px-4 text-sm font-semibold ${link.active
                    ? 'bg-indigo-600 text-white'
                    : link.url
                        ? 'border border-slate-200 bg-white text-slate-700 hover:bg-slate-50'
                        : 'cursor-not-allowed border border-slate-100 bg-slate-50 text-slate-400'
                    }`;

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
                    return (
                        <span key={idx} className={className}>
                            {content}
                        </span>
                    );
                }

                return (
                    <Link key={idx} href={link.url} preserveScroll className={className}>
                        {content}
                    </Link>
                );
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
            Showing <span className="font-semibold text-slate-700">{from}</span>–
            <span className="font-semibold text-slate-700">{to}</span> of{' '}
            <span className="font-semibold text-slate-700">{total}</span>
        </div>
    );
}

function EditJumuiyaModal({ open, onClose, jumuiya, zones }) {
    const { data, setData, patch, processing, errors, clearErrors } = useForm({
        zone_uuid: jumuiya?.zone_uuid ?? '',
        name: jumuiya?.name ?? '',
        meeting_day: jumuiya?.meeting_day ?? '',
        established_year: jumuiya?.established_year ?? '',
        is_active: jumuiya?.is_active ?? true,
    });

    useEffect(() => {
        if (!jumuiya || !open) return;

        clearErrors();
        setData({
            zone_uuid: jumuiya?.zone_uuid ?? '',
            name: jumuiya?.name ?? '',
            meeting_day: jumuiya?.meeting_day ?? '',
            established_year: jumuiya?.established_year ?? '',
            is_active: jumuiya?.is_active ?? true,
        });
    }, [jumuiya?.uuid, open]);

    if (!jumuiya) {
        return null;
    }

    const submit = (e) => {
        e.preventDefault();
        patch(route('jumuiyas.update', jumuiya.uuid), {
            preserveScroll: true,
            onSuccess: () => {
                clearErrors();
                onClose();
            },
        });
    };

    return (
        <Modal show={open} onClose={onClose} maxWidth="xl">
            <div className="max-h-[80vh] overflow-y-auto p-6">
                <ModalHeader
                    title="Edit Christian Community"
                    subtitle="Update Christian community details."
                    onClose={onClose}
                    showRequiredNote
                />

                <form onSubmit={submit} className="mt-4 space-y-4">
                    <div className="grid gap-4 md:grid-cols-2">
                        <FloatingSelect
                            id={`edit_jumuiya_zone_${jumuiya.uuid}`}
                            label="Zone"
                            required
                            value={data.zone_uuid}
                            onChange={(e) => setData('zone_uuid', e.target.value)}
                            error={errors.zone_uuid}
                            className="md:col-span-2"
                        >
                            <option value="">Select zone</option>
                            {(zones ?? []).map((z) => (
                                <option key={z.uuid} value={z.uuid}>{z.name}</option>
                            ))}
                        </FloatingSelect>

                        <FloatingInput
                            id={`edit_jumuiya_name_${jumuiya.uuid}`}
                            label="Community name"
                            required
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            error={errors.name}
                        />
                        <FloatingInput
                            id={`edit_jumuiya_day_${jumuiya.uuid}`}
                            label="Meeting day"
                            value={data.meeting_day}
                            onChange={(e) => setData('meeting_day', e.target.value)}
                            error={errors.meeting_day}
                        />
                        <FloatingInput
                            id={`edit_jumuiya_year_${jumuiya.uuid}`}
                            label="Established year"
                            type="number"
                            min={1800}
                            max={2100}
                            value={data.established_year}
                            onChange={(e) => setData('established_year', e.target.value)}
                            error={errors.established_year}
                        />
                    </div>

                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <label className="flex items-center gap-2 text-sm font-semibold text-slate-700">
                            <input
                                type="checkbox"
                                checked={!!data.is_active}
                                onChange={(e) => setData('is_active', e.target.checked)}
                                className="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                            />
                            Active
                        </label>

                        <div className="flex gap-2">
                            <SecondaryButton
                                type="button"
                                onClick={() => {
                                    clearErrors();
                                    setData({
                                        zone_uuid: jumuiya?.zone_uuid ?? '',
                                        name: jumuiya?.name ?? '',
                                        meeting_day: jumuiya?.meeting_day ?? '',
                                        established_year: jumuiya?.established_year ?? '',
                                        is_active: jumuiya?.is_active ?? true,
                                    });
                                    onClose();
                                }}
                                disabled={processing}
                                className="h-11 rounded-lg text-sm font-semibold normal-case tracking-normal"
                            >
                                Cancel
                            </SecondaryButton>
                            <PrimaryButton
                                disabled={processing}
                                className="h-11 gap-2 rounded-lg text-sm font-semibold normal-case tracking-normal bg-indigo-600 text-white hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-800"
                            >
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
