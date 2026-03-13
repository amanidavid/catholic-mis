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
import { Head, router, useForm, usePage } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';

export default function InstitutionsIndex({ institutions, filters }) {
    const { auth } = usePage().props;
    const permissions = useMemo(() => auth?.user?.permissions ?? [], [auth?.user?.permissions]);

    const canView = useMemo(() => Array.isArray(permissions) && permissions.includes('institutions.view'), [permissions]);
    const canCreate = useMemo(() => Array.isArray(permissions) && permissions.includes('institutions.create'), [permissions]);
    const canUpdate = useMemo(() => Array.isArray(permissions) && permissions.includes('institutions.update'), [permissions]);
    const canDelete = useMemo(() => Array.isArray(permissions) && permissions.includes('institutions.delete'), [permissions]);

    const [q, setQ] = useState(filters?.q ?? '');

    const applySearch = (e) => {
        e.preventDefault();
        router.get(route('institutions.index'), { q: q || undefined }, { preserveState: true, replace: true });
    };

    const clearSearch = () => {
        setQ('');
        router.get(route('institutions.index'), {}, { preserveState: true, replace: true });
    };

    const [modalOpen, setModalOpen] = useState(false);
    const [editing, setEditing] = useState(null);

    const { data, setData, post, patch, processing, errors, clearErrors, reset } = useForm({
        name: '',
        type: '',
        location: '',
        country: '',
        contact: '',
        is_active: true,
    });

    useEffect(() => {
        if (!modalOpen) return;

        clearErrors();
        if (editing) {
            setData({
                name: editing.name ?? '',
                type: editing.type ?? '',
                location: editing.location ?? '',
                country: editing.country ?? '',
                contact: editing.contact ?? '',
                is_active: editing.is_active ?? true,
            });
            return;
        }

        reset();
        setData({ name: '', type: '', location: '', country: '', contact: '', is_active: true });
    }, [modalOpen, editing?.uuid]);

    const closeModal = () => {
        setModalOpen(false);
        setEditing(null);
        clearErrors();
    };

    const submit = (e) => {
        e.preventDefault();

        if (editing?.uuid) {
            patch(route('institutions.update', editing.uuid), {
                preserveScroll: true,
                onSuccess: closeModal,
            });
            return;
        }

        post(route('institutions.store'), {
            preserveScroll: true,
            onSuccess: closeModal,
        });
    };

    const [confirm, setConfirm] = useState({ open: false, institution: null });

    const requestDelete = (i) => {
        setConfirm({ open: true, institution: i });
    };

    const confirmDelete = () => {
        const i = confirm.institution;
        if (!i?.uuid) {
            setConfirm({ open: false, institution: null });
            return;
        }
        router.delete(route('institutions.destroy', i.uuid), {
            preserveScroll: true,
            onSuccess: () => setConfirm({ open: false, institution: null }),
            onFinish: () => setConfirm({ open: false, institution: null }),
        });
    };

    return (
        <AuthenticatedLayout header="Institutions">
            <Head title="Institutions" />

            <div className="mx-auto max-w-6xl space-y-6">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h1 className="text-xl font-semibold text-slate-900">Institutions</h1>
                        <p className="mt-1 text-sm text-slate-500">Manage institutions. Search and pagination are server-side.</p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        {canCreate && (
                            <PrimaryButton
                                type="button"
                                onClick={() => {
                                    setEditing(null);
                                    setModalOpen(true);
                                }}
                                className="h-11 gap-2 rounded-lg text-sm font-semibold normal-case tracking-normal bg-indigo-600 text-white hover:bg-indigo-700"
                            >
                                <span className="text-lg font-bold leading-none">+</span>
                                <span>Institution</span>
                            </PrimaryButton>
                        )}
                    </div>
                </div>

                <section className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
                    {!canView ? (
                        <div className="text-sm font-semibold text-rose-600">You do not have permission to view institutions.</div>
                    ) : (
                        <>
                            <form onSubmit={applySearch} className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                                <div className="w-full sm:max-w-md">
                                    <FloatingInput id="institutions_q" label="Search (name)" value={q} onChange={(e) => setQ(e.target.value)} />
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
                                    <table className="min-w-full divide-y divide-slate-200">
                                        <thead className="bg-slate-50">
                                            <tr>
                                                <th className="w-16 px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">#</th>
                                                <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Name</th>
                                                <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Type</th>
                                                <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Status</th>
                                                {(canUpdate || canDelete) && (
                                                    <th className="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-600">Actions</th>
                                                )}
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-slate-100 bg-white">
                                            {(institutions?.data ?? []).map((i, idx) => (
                                                <tr key={i.uuid} className={idx % 2 === 1 ? 'bg-slate-50/40' : ''}>
                                                    <td className="px-4 py-3 text-sm text-slate-600">{(institutions?.meta?.from ?? 1) + idx}</td>
                                                    <td className="px-4 py-3 text-sm font-semibold text-slate-900">{toTitleCase(i.name)}</td>
                                                    <td className="px-4 py-3 text-sm text-slate-700">{i.type ?? '-'}</td>
                                                    <td className="px-4 py-3 text-sm">
                                                        <span className={`inline-flex items-center rounded-full px-2 py-1 text-xs font-semibold ${i.is_active ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200' : 'bg-slate-100 text-slate-700 ring-1 ring-slate-200'}`}>
                                                            {i.is_active ? 'Active' : 'Inactive'}
                                                        </span>
                                                    </td>
                                                    {(canUpdate || canDelete) && (
                                                        <td className="px-4 py-3 text-right text-sm">
                                                            <div className="flex justify-end gap-2">
                                                                {canUpdate && (
                                                                    <button
                                                                        type="button"
                                                                        onClick={() => {
                                                                            setEditing(i);
                                                                            setModalOpen(true);
                                                                        }}
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
                                                                        onClick={() => requestDelete(i)}
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
                                            ))}
                                            {(institutions?.data ?? []).length === 0 && (
                                                <tr>
                                                    <td colSpan={(canUpdate || canDelete) ? 5 : 4} className="px-4 py-10 text-center text-sm text-slate-500">
                                                        No institutions found.
                                                    </td>
                                                </tr>
                                            )}
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div className="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <PaginationSummary meta={institutions?.meta} />
                                <Pagination links={institutions?.meta?.links ?? institutions?.links ?? []} />
                            </div>
                        </>
                    )}
                </section>
            </div>

            <Modal show={modalOpen} onClose={closeModal} maxWidth="2xl">
                <div className="p-6">
                    <ModalHeader
                        title={editing ? 'Edit institution' : 'Add institution'}
                        subtitle={editing ? 'Update institution details.' : 'Create a new institution.'}
                        onClose={closeModal}
                        showRequiredNote
                    />

                    <form onSubmit={submit} className="mt-4 space-y-4">
                        <div className="grid gap-4 md:grid-cols-2">
                            <FloatingInput id="institution_name" label="Name" required value={data.name} onChange={(e) => setData('name', e.target.value)} error={errors.name} className="md:col-span-2" />
                            <FloatingInput id="institution_type" label="Type" required value={data.type} onChange={(e) => setData('type', e.target.value)} error={errors.type} />
                            <FloatingInput id="institution_country" label="Country" value={data.country} onChange={(e) => setData('country', e.target.value)} error={errors.country} />
                            <FloatingInput id="institution_location" label="Location" value={data.location} onChange={(e) => setData('location', e.target.value)} error={errors.location} />
                            <FloatingInput id="institution_contact" label="Contact" value={data.contact} onChange={(e) => setData('contact', e.target.value)} error={errors.contact} />

                            {editing && (
                                <FloatingSelect
                                    id="institution_is_active"
                                    label="Active"
                                    value={data.is_active ? '1' : '0'}
                                    onChange={(e) => setData('is_active', e.target.value === '1')}
                                    error={errors.is_active}
                                    className="md:col-span-2"
                                >
                                    <option value="1">Yes</option>
                                    <option value="0">No</option>
                                </FloatingSelect>
                            )}
                        </div>

                        <div className="flex flex-wrap items-center justify-end gap-2">
                            <SecondaryButton type="button" onClick={closeModal} disabled={processing} className="h-11 rounded-lg text-sm font-semibold normal-case tracking-normal">
                                Cancel
                            </SecondaryButton>
                            <PrimaryButton disabled={processing} className="h-11 gap-2 rounded-lg text-sm font-semibold normal-case tracking-normal bg-indigo-600 text-white hover:bg-indigo-700">
                                {processing && <Spinner size="sm" className="text-white" />}
                                <span>{editing ? 'Update' : 'Save'}</span>
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </Modal>

            <ConfirmDialog
                open={confirm.open}
                title="Delete institution"
                message="This will permanently remove the institution. If it has assignment history, deletion will be blocked."
                confirmText="Delete"
                onConfirm={confirmDelete}
                onClose={() => setConfirm({ open: false, institution: null })}
            />
        </AuthenticatedLayout>
    );
}

function Pagination({ links }) {
    if (!Array.isArray(links) || links.length === 0) return null;

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

                const label = prev ? 'Prev' : next ? 'Next' : link.label?.replace(/&laquo;|&raquo;/g, '') ?? '';

                return (
                    <button
                        key={idx}
                        type="button"
                        disabled={!link.url}
                        onClick={() => link.url && router.visit(link.url, { preserveState: true, preserveScroll: true })}
                        className={className}
                        dangerouslySetInnerHTML={{ __html: label }}
                    />
                );
            })}
        </div>
    );
}

function PaginationSummary({ meta }) {
    if (!meta) return null;
    return (
        <div className="text-sm text-slate-600">
            Showing <span className="font-semibold">{meta.from ?? 0}</span> to <span className="font-semibold">{meta.to ?? 0}</span> of{' '}
            <span className="font-semibold">{meta.total ?? 0}</span>
        </div>
    );
}
