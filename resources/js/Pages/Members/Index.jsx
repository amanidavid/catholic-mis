import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import ConfirmDialog from '@/Components/ConfirmDialog';
import FloatingInput from '@/Components/FloatingInput';
import FloatingSelect from '@/Components/FloatingSelect';
import Modal from '@/Components/Modal';
import ModalHeader from '@/Components/ModalHeader';
import PrimaryButton from '@/Components/PrimaryButton';
import SearchableFamilySelect from '@/Components/SearchableFamilySelect';
import SearchableJumuiyaSelect from '@/Components/SearchableJumuiyaSelect';
import SearchableZoneSelect from '@/Components/SearchableZoneSelect';
import SecondaryButton from '@/Components/SecondaryButton';
import { toTitleCase } from '@/lib/formatters';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';

export default function MembersIndex({ members, filters, jumuiyas }) {
    const { auth } = usePage().props;
    const permissions = useMemo(() => auth?.user?.permissions ?? [], [auth?.user?.permissions]);

    const canCreate = useMemo(() => Array.isArray(permissions) && permissions.includes('members.create'), [permissions]);
    const canUpdate = useMemo(() => Array.isArray(permissions) && permissions.includes('members.update'), [permissions]);
    const canDelete = useMemo(() => Array.isArray(permissions) && permissions.includes('members.delete'), [permissions]);
    const canTransfer = useMemo(() => Array.isArray(permissions) && permissions.includes('members.transfer'), [permissions]);

    const [q, setQ] = useState(filters?.q ?? '');
    const [searchBy, setSearchBy] = useState(filters?.search_by ?? 'name');
    const [jumuiyaUuid, setJumuiyaUuid] = useState(filters?.jumuiya_uuid ?? '');
    const [familyUuid, setFamilyUuid] = useState(filters?.family_uuid ?? '');

    const [viewing, setViewing] = useState(null);
    const [transferring, setTransferring] = useState(null);

    const applySearch = (e) => {
        e.preventDefault();
        router.get(
            route('members.index'),
            {
                q: q || undefined,
                search_by: searchBy || undefined,
                jumuiya_uuid: jumuiyaUuid || undefined,
                family_uuid: familyUuid || undefined,
            },
            { preserveState: true, replace: true },
        );
    };

    const clearSearch = () => {
        setQ('');
        setSearchBy('name');
        setJumuiyaUuid('');
        setFamilyUuid('');
        router.get(route('members.index'), {}, { preserveState: true, replace: true });
    };

    return (
        <AuthenticatedLayout>
            <Head title="Members" />

            <div className="mx-auto max-w-6xl space-y-6">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h1 className="text-xl font-semibold text-slate-900">Members</h1>
                        <p className="mt-1 text-sm text-slate-500"></p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        {canCreate && (
                            <Link
                                href={route('members.create', {
                                    jumuiya_uuid: jumuiyaUuid || undefined,
                                    family_uuid: familyUuid || undefined,
                                })}
                                className="inline-flex h-11 items-center gap-2 rounded-lg bg-indigo-600 px-4 text-sm font-semibold text-white hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-800"
                            >
                                <span className="text-lg font-bold leading-none">+</span>
                                <span>Member</span>
                            </Link>
                        )}
                    </div>
                </div>

                <section className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
                    <form onSubmit={applySearch} className="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                        <div className="grid w-full gap-3 lg:grid-cols-12">
                            <FloatingInput
                                id="members_q"
                                label="Search"
                                value={q}
                                onChange={(e) => setQ(e.target.value)}
                                hint=""
                                className="lg:col-span-4"
                            />

                            <FloatingSelect
                                id="members_search_by"
                                label="Search by"
                                value={searchBy}
                                onChange={(e) => setSearchBy(e.target.value)}
                                className="lg:col-span-2"
                            >
                                <option value="name">Name</option>
                                <option value="phone">Phone</option>
                                <option value="email">Email</option>
                                <option value="national_id">National ID</option>
                            </FloatingSelect>

                            <FloatingSelect
                                id="members_jumuiya_filter"
                                label="Christian Community"
                                value={jumuiyaUuid}
                                onChange={(e) => {
                                    setJumuiyaUuid(e.target.value);
                                    setFamilyUuid('');
                                }}
                                className="lg:col-span-3"
                            >
                                <option value="">All Christian Communities</option>
                                {(jumuiyas ?? []).map((j) => (
                                    <option key={j.uuid} value={j.uuid}>{j.name}</option>
                                ))}
                            </FloatingSelect>

                            <div className="lg:col-span-4">
                                <SearchableFamilySelect
                                    id="members_family_filter"
                                    label="Family"
                                    value={familyUuid}
                                    onChange={(uuid) => setFamilyUuid(uuid)}
                                    jumuiyaUuid={jumuiyaUuid}
                                />
                            </div>
                        </div>

                        <div className="flex items-center gap-2">
                            <PrimaryButton
                                type="submit"
                                className="h-11 rounded-lg text-sm font-semibold normal-case tracking-normal bg-indigo-600 text-white hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-800"
                            >
                                Search
                            </PrimaryButton>
                            <SecondaryButton
                                type="button"
                                onClick={clearSearch}
                                className="h-11 rounded-lg text-sm font-semibold normal-case tracking-normal"
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
                                        <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Member</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Christian Community</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Family</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Phone</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Status</th>
                                        <th className="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-600">Actions</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100 bg-white">
                                    {(members?.data ?? []).map((m, idx) => (
                                        <MemberRow
                                            key={m.uuid}
                                            member={m}
                                            striped={idx % 2 === 1}
                                            index={(members?.meta?.from ?? 1) + idx}
                                            canUpdate={canUpdate}
                                            canDelete={canDelete}
                                            canTransfer={canTransfer}
                                            onView={() => setViewing(m)}
                                            onEdit={() => router.visit(route('members.edit', m.uuid))}
                                            onTransfer={() => setTransferring(m)}
                                        />
                                    ))}
                                    {(members?.data ?? []).length === 0 && (
                                        <tr>
                                            <td colSpan={7} className="px-4 py-10 text-center text-sm text-slate-500">
                                                No members found.
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div className="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <PaginationSummary meta={members?.meta} />
                        <Pagination links={members?.meta?.links ?? members?.links ?? []} />
                    </div>
                </section>
            </div>

            <Modal show={!!viewing} onClose={() => setViewing(null)} maxWidth="2xl">
                <div className="flex max-h-[85vh] flex-col">
                    <div className="border-b border-slate-200 bg-white px-6 py-4">
                        <div className="flex items-start justify-between gap-4">
                            <div className="min-w-0">
                                <div className="flex flex-wrap items-center gap-2">
                                    <h2 className="truncate text-lg font-semibold text-slate-900">{viewing?.full_name ?? 'Member details'}</h2>
                                    {viewing && (
                                        <span
                                            className={`inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ${viewing.is_active
                                                ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200'
                                                : 'bg-rose-50 text-rose-700 ring-1 ring-rose-200'
                                                }`}
                                        >
                                            {viewing.is_active ? 'Active' : 'Inactive'}
                                        </span>
                                    )}
                                </div>
                                <p className="mt-1 text-sm text-slate-500">Profile overview</p>
                            </div>
                            <button
                                type="button"
                                onClick={() => setViewing(null)}
                                className="inline-flex h-10 items-center rounded-lg border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                            >
                                Close
                            </button>
                        </div>
                    </div>

                    <div className="flex-1 overflow-auto px-6 py-5">
                        {viewing && (
                            <div className="grid gap-4 md:grid-cols-2">
                                <DetailItem label="Christian Community" value={viewing.jumuiya_name ?? '-'} />
                                <DetailItem label="Family" value={viewing.family_name ?? '-'} />
                                <DetailItem label="Family relationship" value={viewing.family_relationship_name ?? '-'} />
                                <DetailItem label="Head of family" value={viewing.is_head_of_family ? 'Yes' : 'No'} />
                                <DetailItem
                                    label="System roles"
                                    value={Array.isArray(viewing.system_roles) && viewing.system_roles.length > 0
                                        ? viewing.system_roles.join(', ')
                                        : '-'}
                                />
                                <DetailItem label="Gender" value={viewing.gender ?? '-'} />
                                <DetailItem label="Phone" value={viewing.phone ?? '-'} />
                                <DetailItem label="Email" value={viewing.email ?? '-'} />
                                <DetailItem label="Birth date" value={viewing.birth_date ?? '-'} />
                                <DetailItem label="National ID" value={viewing.national_id ?? '-'} />
                                <DetailItem label="Marital status" value={viewing.marital_status ?? '-'} />
                            </div>
                        )}
                    </div>
                </div>
            </Modal>

            <TransferMemberModal
                open={!!transferring}
                onClose={() => setTransferring(null)}
                member={transferring}
                canTransfer={canTransfer}
            />

        </AuthenticatedLayout>
    );
}

function MemberRow({ member, striped = false, index, canUpdate, canDelete, canTransfer, onView, onEdit, onTransfer }) {
    const [confirmOpen, setConfirmOpen] = useState(false);

    const destroy = () => {
        router.delete(route('members.destroy', member.uuid), { preserveScroll: true });
    };

    return (
        <>
            <tr className={`${striped ? 'bg-slate-50/50' : 'bg-white'} hover:bg-indigo-50/40 transition`}>
                <td className="px-4 py-3 text-sm font-semibold text-slate-700">{index}</td>
                <td className="px-4 py-3">
                    <div className="text-sm font-semibold text-slate-900">{toTitleCase(member.full_name)}</div>
                    <div className="text-xs text-slate-500">{member.gender ? member.gender : '-'}</div>
                </td>
                <td className="px-4 py-3 text-sm text-slate-700">{toTitleCase(member.jumuiya_name ?? '-')}</td>
                <td className="px-4 py-3 text-sm text-slate-700">{toTitleCase(member.family_name ?? '-')}</td>
                <td className="px-4 py-3 text-sm text-slate-700">{member.phone ?? '-'}</td>
                <td className="px-4 py-3 text-sm">
                    <span
                        className={`inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ${member.is_active
                            ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200'
                            : 'bg-rose-50 text-rose-700 ring-1 ring-rose-200'
                            }`}
                    >
                        {member.is_active ? 'Active' : 'Inactive'}
                    </span>
                </td>
                <td className="px-4 py-3">
                    <div className="flex items-center justify-end gap-2">
                        <button
                            type="button"
                            onClick={onView}
                            className="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-700 hover:bg-slate-50"
                            title="View"
                        >
                            <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                <path strokeLinecap="round" strokeLinejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </button>
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
                        {canTransfer && (
                            <button
                                type="button"
                                onClick={onTransfer}
                                className="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-cyan-200 bg-cyan-50 text-cyan-800 hover:bg-cyan-100"
                                title="Transfer"
                            >
                                <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M4 7h11M4 17h11M15 10l3-3m0 0l-3-3m3 3H9m6 10l3-3m0 0l-3-3m3 3H9" />
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
            </tr>

            <ConfirmDialog
                open={confirmOpen}
                onClose={() => setConfirmOpen(false)}
                title="Delete Member"
                message={`Are you sure you want to delete "${toTitleCase(member.full_name)}"?`}
                confirmText="Delete"
                onConfirm={destroy}
            />
        </>
    );
}

function TransferMemberModal({ open, onClose, member, canTransfer }) {
    const { data, setData, processing, errors, reset, post } = useForm({
        zone_uuid: '',
        jumuiya_uuid: '',
        family_uuid: '',
        effective_date: '',
        reason: '',
    });

    useEffect(() => {
        if (!open) return;
        setData({
            zone_uuid: '',
            jumuiya_uuid: '',
            family_uuid: '',
            effective_date: '',
            reason: '',
        });
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [open, member?.uuid]);

    const close = () => {
        onClose();
        reset();
    };

    const submit = (e) => {
        e.preventDefault();
        if (!member?.uuid) return;
        if (!canTransfer) return;

        post(route('members.transfer', member.uuid), {
            preserveScroll: true,
            onSuccess: () => {
                close();
                router.reload({ only: ['members'], preserveScroll: true });
            },
        });
    };

    return (
        <Modal show={open} onClose={close} maxWidth="lg">
            <div className="flex max-h-[85vh] min-h-0 flex-col bg-white">
                <form onSubmit={submit} className="flex min-h-0 flex-1 flex-col p-6">
                    <ModalHeader
                        title="Transfer member"
                        subtitle={member?.full_name ? toTitleCase(member.full_name) : ''}
                        onClose={close}
                    />

                    <div className="min-h-0 flex-1 overflow-y-auto pr-1">
                        <div className="space-y-5">
                            <div className="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                                Select Zone, Christian Community and Family. This will move the member under the selected Family.
                            </div>

                            <SearchableZoneSelect
                                id="member_transfer_zone_uuid"
                                label="Zone"
                                value={data.zone_uuid}
                                onChange={(uuid) => {
                                    setData('zone_uuid', uuid);
                                    if (data.jumuiya_uuid) setData('jumuiya_uuid', '');
                                    if (data.family_uuid) setData('family_uuid', '');
                                }}
                                error={errors.zone_uuid}
                            />

                            <SearchableJumuiyaSelect
                                id="member_transfer_jumuiya_uuid"
                                label="Christian Community (Jumuiya)"
                                value={data.jumuiya_uuid}
                                onChange={(uuid) => {
                                    setData('jumuiya_uuid', uuid);
                                    if (data.family_uuid) setData('family_uuid', '');
                                }}
                                zoneUuid={data.zone_uuid}
                                disabled={!data.zone_uuid || processing}
                                error={errors.jumuiya_uuid}
                            />

                            <SearchableFamilySelect
                                id="member_transfer_family_uuid"
                                label="Family"
                                value={data.family_uuid}
                                onChange={(uuid) => setData('family_uuid', uuid)}
                                jumuiyaUuid={data.jumuiya_uuid}
                                disabled={!data.jumuiya_uuid || processing}
                                error={errors.family_uuid}
                            />

                            <FloatingInput
                                id="member_transfer_effective_date"
                                label="Effective date (optional)"
                                type="date"
                                value={data.effective_date}
                                onChange={(e) => setData('effective_date', e.target.value)}
                                error={errors.effective_date}
                            />

                            <FloatingInput
                                id="member_transfer_reason"
                                label="Reason (optional)"
                                value={data.reason}
                                onChange={(e) => setData('reason', e.target.value)}
                                error={errors.reason}
                            />
                        </div>
                    </div>

                    <div className="shrink-0 -mx-6 mt-5 border-t border-slate-200 bg-white px-6 pt-4">
                        <div className="flex items-center justify-end gap-2">
                            <SecondaryButton type="button" onClick={close} className="h-11 rounded-lg text-sm font-semibold normal-case tracking-normal">
                                Cancel
                            </SecondaryButton>
                            <PrimaryButton
                                type="submit"
                                disabled={processing || !canTransfer || !data.jumuiya_uuid || !data.family_uuid}
                                className="h-11 rounded-lg text-sm font-semibold normal-case tracking-normal bg-cyan-600 text-white hover:bg-cyan-700 focus:bg-cyan-700 active:bg-cyan-800"
                            >
                                Transfer
                            </PrimaryButton>
                        </div>
                    </div>
                </form>
            </div>
        </Modal>
    );
}

function DetailItem({ label, value }) {
    return (
        <div className="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
            <div className="text-xs font-semibold uppercase tracking-wide text-slate-500">{label}</div>
            <div className="mt-1 text-sm font-semibold text-slate-900 break-words">{value}</div>
        </div>
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
