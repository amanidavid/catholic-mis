import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import ConfirmDialog from '@/Components/ConfirmDialog';
import FloatingInput from '@/Components/FloatingInput';
import Modal from '@/Components/Modal';
import ModalHeader from '@/Components/ModalHeader';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import Spinner from '@/Components/Spinner';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { useEffect, useState } from 'react';

export default function ServiceCategoriesIndex({ categories, filters, can }) {
    const rows = categories?.data ?? [];
    const [q, setQ] = useState(filters?.q ?? '');
    const [status, setStatus] = useState(filters?.status ?? 'all');
    const [modalOpen, setModalOpen] = useState(false);
    const [editing, setEditing] = useState(null);
    const [deleteTarget, setDeleteTarget] = useState(null);

    const form = useForm({
        name: '',
        code: '',
        description: '',
        sort_order: 0,
        is_active: true,
    });

    const canCreate = !!can?.create;
    const canUpdate = !!can?.update;
    const canDelete = !!can?.delete;

    const runSearch = (event) => {
        event?.preventDefault?.();
        router.get(route('pastoral.service-categories.index'), {
            q: q || undefined,
            status: status === 'all' ? undefined : status,
        }, { preserveState: true, replace: true, preserveScroll: true });
    };

    const clearSearch = () => {
        setQ('');
        setStatus('all');
        router.get(route('pastoral.service-categories.index'), {}, { preserveState: true, replace: true, preserveScroll: true });
    };

    useEffect(() => {
        if (!modalOpen) return;

        form.clearErrors();
        if (editing) {
            form.setData({
                name: editing.name ?? '',
                code: editing.code ?? '',
                description: editing.description ?? '',
                sort_order: editing.sort_order ?? 0,
                is_active: !!editing.is_active,
            });
            return;
        }

        form.setData({
            name: '',
            code: '',
            description: '',
            sort_order: (rows.length + 1),
            is_active: true,
        });
    }, [modalOpen, editing?.uuid]);

    const submit = (event) => {
        event.preventDefault();

        const options = {
            preserveScroll: true,
            onSuccess: () => {
                setModalOpen(false);
                setEditing(null);
            },
        };

        if (editing?.uuid) {
            form.patch(route('pastoral.service-categories.update', editing.uuid), options);
            return;
        }

        form.post(route('pastoral.service-categories.store'), options);
    };

    return (
        <AuthenticatedLayout>
            <Head title="Service Categories" />

            <div className="mx-auto max-w-7xl space-y-6">
                <section className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
                    <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <h1 className="text-xl font-semibold text-slate-900">Service Categories</h1>
                            <p className="mt-1 text-sm text-slate-500">Manage parish-specific service category master data.</p>
                        </div>
                        {canCreate && (
                            <PrimaryButton
                                type="button"
                                onClick={() => {
                                    setEditing(null);
                                    setModalOpen(true);
                                }}
                                className="h-11 rounded-lg bg-indigo-600 px-4 text-sm font-semibold normal-case text-white hover:bg-indigo-700"
                            >
                                New Category
                            </PrimaryButton>
                        )}
                    </div>
                </section>

                <section className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
                    <form onSubmit={runSearch} className="flex flex-col gap-3 lg:flex-row lg:items-end">
                        <div className="min-w-0 flex-1">
                            <FloatingInput
                                id="service_categories_q"
                                label="Search (name/code)"
                                value={q}
                                onChange={(e) => setQ(e.target.value)}
                            />
                        </div>
                        <div className="w-full lg:w-48">
                            <label className="mb-1 block text-xs font-semibold text-slate-600">Status</label>
                            <select
                                value={status}
                                onChange={(e) => setStatus(e.target.value)}
                                className="h-11 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-900 focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="all">All</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <PrimaryButton type="submit" className="h-11 rounded-lg bg-indigo-600 px-5 text-sm font-semibold normal-case text-white hover:bg-indigo-700">Search</PrimaryButton>
                        <SecondaryButton type="button" onClick={clearSearch} className="h-11 rounded-lg px-5 text-sm font-semibold normal-case">Clear</SecondaryButton>
                    </form>

                    <div className="mt-6 overflow-x-auto">
                        <div className="overflow-hidden rounded-xl ring-1 ring-slate-200">
                            <table className="mis-table divide-y divide-slate-200">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Code</th>
                                        <th>Sort</th>
                                        <th>Status</th>
                                        {(canUpdate || canDelete) && <th className="text-right">Actions</th>}
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100">
                                    {rows.length === 0 ? (
                                        <tr>
                                            <td colSpan={(canUpdate || canDelete) ? 5 : 4} className="px-4 py-10 text-center text-sm text-slate-500">No service categories found.</td>
                                        </tr>
                                    ) : rows.map((row, idx) => (
                                        <tr key={row.uuid} className={idx % 2 ? 'bg-slate-50/40' : 'bg-white'}>
                                            <td className="px-4 py-3">
                                                <div className="font-semibold text-slate-900">{row.name}</div>
                                                {row.description && <div className="mt-1 text-xs text-slate-500">{row.description}</div>}
                                            </td>
                                            <td className="px-4 py-3 text-sm text-slate-700">{row.code}</td>
                                            <td className="px-4 py-3 text-sm text-slate-700">{row.sort_order ?? 0}</td>
                                            <td className="px-4 py-3 text-sm">
                                                <span className={`inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ${row.is_active ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200' : 'bg-rose-50 text-rose-700 ring-1 ring-rose-200'}`}>
                                                    {row.is_active ? 'Active' : 'Inactive'}
                                                </span>
                                            </td>
                                            {(canUpdate || canDelete) && (
                                                <td className="px-4 py-3 text-right">
                                                    <div className="inline-flex gap-2">
                                                        {canUpdate && (
                                                            <button
                                                                type="button"
                                                                onClick={() => {
                                                                    setEditing(row);
                                                                    setModalOpen(true);
                                                                }}
                                                                className="rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-2 text-xs font-semibold text-indigo-700 hover:bg-indigo-100"
                                                            >
                                                                Edit
                                                            </button>
                                                        )}
                                                        {canDelete && (
                                                            <button
                                                                type="button"
                                                                onClick={() => setDeleteTarget(row)}
                                                                className="rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-100"
                                                            >
                                                                Delete
                                                            </button>
                                                        )}
                                                    </div>
                                                </td>
                                            )}
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div className="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <PaginationSummary meta={categories?.meta} />
                        <Pagination links={categories?.meta?.links ?? categories?.links ?? []} />
                    </div>
                </section>
            </div>

            <Modal show={modalOpen} onClose={() => setModalOpen(false)} maxWidth="2xl">
                <div className="p-6">
                    <ModalHeader
                        title={editing ? 'Edit Service Category' : 'New Service Category'}
                        subtitle="Create or update category details."
                        onClose={() => setModalOpen(false)}
                        showRequiredNote
                    />

                    <form onSubmit={submit} className="mt-4 space-y-4">
                        <FloatingInput id="category_name" label="Name" required value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} error={form.errors.name} />
                        <FloatingInput id="category_code" label="Code" required value={form.data.code} onChange={(e) => form.setData('code', e.target.value)} error={form.errors.code} />
                        <FloatingInput id="category_description" label="Description" value={form.data.description} onChange={(e) => form.setData('description', e.target.value)} error={form.errors.description} />
                        <FloatingInput id="category_sort" label="Sort order" type="number" value={form.data.sort_order} onChange={(e) => form.setData('sort_order', e.target.value)} error={form.errors.sort_order} />
                        <label className="flex items-center gap-2 text-sm font-semibold text-slate-700">
                            <input type="checkbox" checked={!!form.data.is_active} onChange={(e) => form.setData('is_active', e.target.checked)} className="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" />
                            Active
                        </label>

                        <div className="flex justify-end gap-2">
                            <SecondaryButton type="button" onClick={() => setModalOpen(false)} disabled={form.processing} className="h-11 rounded-lg text-sm font-semibold normal-case">Cancel</SecondaryButton>
                            <PrimaryButton disabled={form.processing} className="h-11 rounded-lg bg-indigo-600 px-4 text-sm font-semibold normal-case text-white hover:bg-indigo-700">
                                {form.processing && <Spinner size="sm" className="text-white" />}
                                <span>{editing ? 'Update' : 'Save'}</span>
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </Modal>

            <ConfirmDialog
                open={!!deleteTarget}
                onClose={() => setDeleteTarget(null)}
                title="Delete service category"
                message={deleteTarget ? `Are you sure you want to delete "${deleteTarget.name}"?` : ''}
                confirmText="Delete"
                onConfirm={() => {
                    if (!deleteTarget?.uuid) return;
                    router.delete(route('pastoral.service-categories.destroy', deleteTarget.uuid), {
                        preserveScroll: true,
                        onFinish: () => setDeleteTarget(null),
                    });
                }}
            />
        </AuthenticatedLayout>
    );
}

function Pagination({ links }) {
    if (!Array.isArray(links) || links.length === 0) return null;

    return (
        <div className="flex flex-wrap gap-2">
            {links.map((link, idx) => (
                link.url ? (
                    <Link
                        key={idx}
                        href={link.url}
                        preserveScroll
                        className={`inline-flex h-11 items-center justify-center rounded-lg px-4 text-sm font-semibold ${link.active ? 'bg-indigo-600 text-white' : 'border border-slate-200 bg-white text-slate-700 hover:bg-slate-50'}`}
                    >
                        <span dangerouslySetInnerHTML={{ __html: link.label }} />
                    </Link>
                ) : (
                    <span key={idx} className={`inline-flex h-11 items-center justify-center rounded-lg px-4 text-sm font-semibold ${link.active ? 'bg-indigo-600 text-white' : 'cursor-not-allowed border border-slate-100 bg-slate-50 text-slate-400'}`}>
                        <span dangerouslySetInnerHTML={{ __html: link.label }} />
                    </span>
                )
            ))}
        </div>
    );
}

function PaginationSummary({ meta }) {
    if (!meta || typeof meta !== 'object') return null;
    const from = meta.from ?? 0;
    const to = meta.to ?? 0;
    const total = meta.total ?? 0;

    return total
        ? <div className="text-sm text-slate-500">Showing <span className="font-semibold text-slate-700">{from}</span>-<span className="font-semibold text-slate-700">{to}</span> of <span className="font-semibold text-slate-700">{total}</span></div>
        : <div className="text-sm text-slate-500">Showing 0 results</div>;
}
