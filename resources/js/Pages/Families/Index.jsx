import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import ConfirmDialog from '@/Components/ConfirmDialog';
import FloatingInput from '@/Components/FloatingInput';
import FloatingSelect from '@/Components/FloatingSelect';
import Modal from '@/Components/Modal';
import ModalHeader from '@/Components/ModalHeader';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import SearchableJumuiyaSelect from '@/Components/SearchableJumuiyaSelect';
import SearchableZoneSelect from '@/Components/SearchableZoneSelect';
import Spinner from '@/Components/Spinner';
import { toTitleCase } from '@/lib/formatters';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';

export default function FamiliesIndex({ families, filters, jumuiyas }) {
    const { auth } = usePage().props;

    const permissions = useMemo(() => auth?.user?.permissions ?? [], [auth?.user?.permissions]);

    const canViewJumuiyas = useMemo(() => Array.isArray(permissions) && permissions.includes('jumuiyas.view'), [permissions]);
    const isScopedToSingleJumuiya = useMemo(() => !canViewJumuiyas && Array.isArray(jumuiyas) && jumuiyas.length === 1, [canViewJumuiyas, jumuiyas]);

    const canCreate = useMemo(() => Array.isArray(permissions) && permissions.includes('families.create'), [permissions]);
    const canUpdate = useMemo(() => Array.isArray(permissions) && permissions.includes('families.update'), [permissions]);
    const canDelete = useMemo(() => Array.isArray(permissions) && permissions.includes('families.delete'), [permissions]);

    const [q, setQ] = useState(filters?.q ?? '');
    const [jumuiyaUuid, setJumuiyaUuid] = useState(filters?.jumuiya_uuid ?? '');

    const applySearch = (e) => {
        e.preventDefault();
        router.get(
            route('families.index'),
            {
                q: q || undefined,
                jumuiya_uuid: jumuiyaUuid || undefined,
            },
            { preserveState: true, replace: true },
        );
    };

    const clearSearch = () => {
        setQ('');
        setJumuiyaUuid('');
        router.get(route('families.index'), {}, { preserveState: true, replace: true });
    };

    const [modalOpen, setModalOpen] = useState(false);
    const [editing, setEditing] = useState(null);

    const closeModal = () => {
        setModalOpen(false);
        setEditing(null);
    };

    const {
        data,
        setData,
        post,
        patch,
        processing,
        errors,
        clearErrors,
        reset,
    } = useForm({
        zone_uuid: '',
        jumuiya_uuid: '',
        family_name: '',
        family_code: '',
        house_number: '',
        street: '',
        is_active: true,
    });

    useEffect(() => {
        if (!modalOpen) return;

        clearErrors();

        if (editing) {
            setData({
                zone_uuid: editing?.zone_uuid ?? '',
                jumuiya_uuid: editing?.jumuiya_uuid ?? '',
                family_name: editing?.family_name ?? '',
                family_code: editing?.family_code ?? '',
                house_number: editing?.house_number ?? '',
                street: editing?.street ?? '',
                is_active: editing?.is_active ?? true,
            });
            return;
        }

        reset();
        setData({
            zone_uuid: '',
            jumuiya_uuid: isScopedToSingleJumuiya ? (jumuiyas?.[0]?.uuid ?? '') : (jumuiyaUuid || ''),
            family_name: '',
            family_code: '',
            house_number: '',
            street: '',
            is_active: true,
        });
    }, [modalOpen, editing?.uuid, isScopedToSingleJumuiya, jumuiyas?.[0]?.uuid]);

    const openAdd = () => {
        setEditing(null);
        setModalOpen(true);
    };

    const openEdit = (family) => {
        setEditing(family);
        setModalOpen(true);
    };

    const submit = (e) => {
        e.preventDefault();

        if (editing) {
            patch(route('families.update', editing.uuid), {
                preserveScroll: true,
                onSuccess: closeModal,
            });
            return;
        }

        post(route('families.store'), {
            preserveScroll: true,
            onSuccess: closeModal,
        });
    };

    return (
        <AuthenticatedLayout>
            <Head title="Families" />

            <div className="mx-auto max-w-6xl space-y-6">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h1 className="text-xl font-semibold text-slate-900">Families</h1>
                        <p className="mt-1 text-sm text-slate-500"></p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        {canCreate && (
                            <PrimaryButton
                                type="button"
                                onClick={openAdd}
                                className="h-11 gap-2 rounded-lg text-sm font-semibold normal-case tracking-normal bg-indigo-600 text-white hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-800"
                            >
                                <span className="text-lg font-bold leading-none">+</span>
                                <span>Family</span>
                            </PrimaryButton>
                        )}
                    </div>
                </div>

                <section className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
                    <form onSubmit={applySearch} className="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                        <div className="grid w-full gap-3 sm:grid-cols-2 lg:max-w-2xl">
                            <FloatingInput
                                id="families_q"
                                label="Search (name or code)"
                                value={q}
                                onChange={(e) => setQ(e.target.value)}
                                hint=""
                            />

                            <FloatingSelect
                                id="families_jumuiya_filter"
                                label="Christian Community"
                                value={jumuiyaUuid}
                                onChange={(e) => setJumuiyaUuid(e.target.value)}
                            >
                                <option value="">All Christian Communities</option>
                                {(jumuiyas ?? []).map((j) => (
                                    <option key={j.uuid} value={j.uuid}>{j.name}</option>
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
                            <table className="min-w-full divide-y divide-slate-200">
                                <thead className="bg-slate-50">
                                    <tr>
                                        <th className="w-16 px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">#</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Family</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Code</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Christian Community</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Head</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Status</th>
                                        {(canUpdate || canDelete) && (
                                            <th className="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-600">Actions</th>
                                        )}
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100 bg-white">
                                    {(families?.data ?? []).map((f, idx) => (
                                        <FamilyRow
                                            key={f.uuid}
                                            family={f}
                                            striped={idx % 2 === 1}
                                            index={(families?.meta?.from ?? 1) + idx}
                                            canUpdate={canUpdate}
                                            canDelete={canDelete}
                                            onEdit={() => openEdit(f)}
                                        />
                                    ))}
                                    {(families?.data ?? []).length === 0 && (
                                        <tr>
                                            <td colSpan={(canUpdate || canDelete) ? 7 : 6} className="px-4 py-10 text-center text-sm text-slate-500">
                                                No families found.
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div className="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <PaginationSummary meta={families?.meta} />
                        <Pagination links={families?.meta?.links ?? families?.links ?? []} />
                    </div>
                </section>
            </div>

            <Modal show={modalOpen} onClose={closeModal} maxWidth="2xl">
                <div className="p-6">
                    <ModalHeader
                        title={editing ? 'Edit family' : 'Add family'}
                        subtitle={editing ? 'Update family details.' : 'Create a new family.'}
                        onClose={closeModal}
                        showRequiredNote
                    />

                    <form onSubmit={submit} className="mt-4 space-y-4">
                        <div className="grid gap-4 md:grid-cols-2">
                            {isScopedToSingleJumuiya ? (
                                <FloatingSelect
                                    id="family_jumuiya_uuid"
                                    label="Christian Community"
                                    value={data.jumuiya_uuid}
                                    onChange={(e) => setData('jumuiya_uuid', e.target.value)}
                                    error={errors.jumuiya_uuid}
                                    className="md:col-span-2"
                                    required
                                >
                                    {(jumuiyas ?? []).map((j) => (
                                        <option key={j.uuid} value={j.uuid}>{j.name}</option>
                                    ))}
                                </FloatingSelect>
                            ) : (
                                <>
                                    <SearchableZoneSelect
                                        id="family_zone_uuid"
                                        label="Zone"
                                        value={data.zone_uuid}
                                        onChange={(uuid) => {
                                            setData('zone_uuid', uuid);
                                            setData('jumuiya_uuid', '');
                                        }}
                                        error={errors.zone_uuid}
                                        className="md:col-span-2"
                                    />

                                    <SearchableJumuiyaSelect
                                        id="family_jumuiya_uuid"
                                        label="Christian Community"
                                        value={data.jumuiya_uuid}
                                        onChange={(uuid) => setData('jumuiya_uuid', uuid)}
                                        zoneUuid={data.zone_uuid}
                                        disabled={!data.zone_uuid}
                                        error={errors.jumuiya_uuid}
                                        className="md:col-span-2"
                                    />
                                </>
                            )}

                            <FloatingInput
                                id="family_name"
                                label="Family name"
                                required
                                value={data.family_name}
                                onChange={(e) => setData('family_name', e.target.value)}
                                error={errors.family_name}
                            />

                            <FloatingInput
                                id="family_code"
                                label="Family code"
                                value={data.family_code}
                                onChange={(e) => setData('family_code', e.target.value)}
                                error={errors.family_code}
                            />

                            <FloatingInput
                                id="house_number"
                                label="House number"
                                value={data.house_number}
                                onChange={(e) => setData('house_number', e.target.value)}
                                error={errors.house_number}
                            />

                            <FloatingInput
                                id="street"
                                label="Street"
                                value={data.street}
                                onChange={(e) => setData('street', e.target.value)}
                                error={errors.street}
                            />
                        </div>

                        <div className="flex flex-wrap items-center justify-between gap-3">
                            {editing && (
                                <label className="flex items-center gap-2 text-sm font-semibold text-slate-700">
                                    <input
                                        type="checkbox"
                                        checked={!!data.is_active}
                                        onChange={(e) => setData('is_active', e.target.checked)}
                                        className="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                    />
                                    Active
                                </label>
                            )}

                            <div className="flex gap-2">
                                <SecondaryButton
                                    type="button"
                                    onClick={closeModal}
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
                                    <span>{editing ? 'Update' : 'Save'}</span>
                                </PrimaryButton>
                            </div>
                        </div>
                    </form>
                </div>
            </Modal>
        </AuthenticatedLayout>
    );

    function FamilyRow({ family, striped = false, index, canUpdate, canDelete, onEdit }) {
        const [confirmOpen, setConfirmOpen] = useState(false);

        const destroy = () => {
            router.delete(route('families.destroy', family.uuid), { preserveScroll: true });
        };

        return (
            <>
                <tr className={`${striped ? 'bg-slate-50/50' : 'bg-white'} hover:bg-indigo-50/40 transition`}>
                    <td className="px-4 py-3 text-sm font-semibold text-slate-700">{index}</td>
                    <td className="px-4 py-3 text-sm font-medium text-slate-900">{toTitleCase(family.family_name)}</td>
                    <td className="px-4 py-3 text-sm text-slate-700">{family.family_code ?? '-'}</td>
                    <td className="px-4 py-3 text-sm text-slate-700">{toTitleCase(family.jumuiya_name ?? '-')}</td>
                    <td className="px-4 py-3 text-sm text-slate-700">{toTitleCase(family.head_of_family_member_name ?? '-')}</td>
                    <td className="px-4 py-3 text-sm">
                        <span
                            className={`inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ${family.is_active
                                ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200'
                                : 'bg-rose-50 text-rose-700 ring-1 ring-rose-200'
                                }`}
                        >
                            {family.is_active ? 'Active' : 'Inactive'}
                        </span>
                    </td>
                    {(canUpdate || canDelete) && (
                        <td className="px-4 py-3">
                            <div className="flex items-center justify-end gap-2">
                                {canUpdate && (
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
                                )}
                                {canDelete && (
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
                                )}
                            </div>
                        </td>
                    )}
                </tr>

                <ConfirmDialog
                    open={confirmOpen}
                    onClose={() => setConfirmOpen(false)}
                    title="Delete family"
                    message={`Are you sure you want to delete "${toTitleCase(family.family_name)}"?`}
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
                        <a
                            key={idx}
                            href={link.url}
                            className={className}
                            onClick={(e) => {
                                e.preventDefault();
                                router.get(link.url, {}, { preserveScroll: true, preserveState: true, replace: true });
                            }}
                        >
                            {content}
                        </a>
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

}
