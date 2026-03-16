import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import { Head, router, usePage } from '@inertiajs/react';
import { useMemo } from 'react';

export default function BaptismCertificate({ baptism, schedule }) {
    const { auth } = usePage().props;
    const baptismData = baptism?.data ?? baptism;

    const parishName = baptismData?.parish?.name ?? baptismData?.zone?.parish?.name ?? '—';
    const communityName = baptismData?.origin_jumuiya?.name ?? '—';

    const childName = baptismData?.member?.full_name
        ?? [baptismData?.member?.first_name, baptismData?.member?.middle_name, baptismData?.member?.last_name].filter(Boolean).join(' ');

    const sponsor = useMemo(() => {
        const s = (baptismData?.sponsors ?? [])[0];
        if (!s) return null;
        const name = s?.member
            ? [s.member.first_name, s.member.middle_name, s.member.last_name].filter(Boolean).join(' ')
            : (s.full_name ?? null);
        return {
            name: name || '—',
            role: s.role || '—',
            parish: s.parish_name || '—',
            phone: s?.member?.phone ?? s?.phone ?? null,
            email: s?.member?.email ?? s?.email ?? null,
        };
    }, [baptismData]);

    const fatherName = baptismData?.father?.full_name ?? '—';
    const motherName = baptismData?.mother?.full_name ?? '—';
    const fatherPhone = baptismData?.father?.phone ?? null;
    const fatherEmail = baptismData?.father?.email ?? null;
    const motherPhone = baptismData?.mother?.phone ?? null;
    const motherEmail = baptismData?.mother?.email ?? null;

    const baptismDate = baptismData?.baptism_date ?? schedule?.scheduled_for ?? '—';

    const issuedBy = baptismData?.issued_by?.name ?? '—';
    const issuedAt = baptismData?.issued_at ?? '—';

    return (
        <AuthenticatedLayout>
            <Head title="Baptism Certificate" />

            <style>{`
                @media print {
                    .no-print { display: none !important; }
                    body { background: white !important; }
                }
            `}</style>

            <div className="mx-auto max-w-4xl px-4 py-6 sm:px-6 lg:px-8">
                <div className="no-print mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight text-slate-900">Baptism Certificate</h1>
                        <p className="mt-1 text-sm text-slate-600">Print-ready certificate view.</p>
                    </div>
                    <div className="flex flex-wrap items-center gap-2">
                        <SecondaryButton type="button" className="h-11" onClick={() => router.get(route('baptisms.show', baptismData.uuid))}>
                            Back
                        </SecondaryButton>
                        <PrimaryButton type="button" className="h-11 bg-slate-900 hover:bg-slate-950" onClick={() => window.print()}>
                            Print
                        </PrimaryButton>
                    </div>
                </div>

                <div className="bg-white p-8 ring-1 ring-slate-200">
                    <div className="text-center">
                        <div className="text-sm font-semibold uppercase tracking-wide text-slate-700">Roman Catholic Church</div>
                        <div className="mt-1 text-xl font-semibold text-slate-900">{parishName}</div>
                        <div className="mt-2 text-sm text-slate-700">Certificate No: <span className="font-semibold text-slate-900">{baptismData?.certificate_no ?? '—'}</span></div>
                    </div>

                    <div className="mt-8 border-t border-slate-200 pt-6">
                        <div className="text-center text-lg font-semibold text-slate-900">Certificate of Baptism</div>
                        <div className="mt-1 text-center text-sm text-slate-600">This is to certify that the following baptism was duly recorded.</div>
                    </div>

                    <div className="mt-8 grid gap-6 sm:grid-cols-2">
                        <div>
                            <div className="text-xs font-semibold uppercase tracking-wide text-slate-600">Baptized person</div>
                            <div className="mt-1 text-sm text-slate-900"><span className="font-semibold">Full name:</span> {childName || '—'}</div>
                            <div className="mt-1 text-sm text-slate-900"><span className="font-semibold">Date of birth:</span> {baptismData?.birth_date ?? '—'}</div>
                            <div className="mt-1 text-sm text-slate-900"><span className="font-semibold">Christian community:</span> {communityName}</div>
                        </div>

                        <div>
                            <div className="text-xs font-semibold uppercase tracking-wide text-slate-600">Parents</div>
                            <div className="mt-1 text-sm text-slate-900"><span className="font-semibold">Father:</span> {fatherName}</div>
                            {(fatherPhone || fatherEmail) && (
                                <div className="mt-1 text-xs text-slate-600">
                                    {fatherPhone ?? '—'}
                                    {fatherEmail ? ` • ${fatherEmail}` : ''}
                                </div>
                            )}
                            <div className="mt-1 text-sm text-slate-900"><span className="font-semibold">Mother:</span> {motherName}</div>
                            {(motherPhone || motherEmail) && (
                                <div className="mt-1 text-xs text-slate-600">
                                    {motherPhone ?? '—'}
                                    {motherEmail ? ` • ${motherEmail}` : ''}
                                </div>
                            )}
                        </div>

                        <div>
                            <div className="text-xs font-semibold uppercase tracking-wide text-slate-600">Baptism details</div>
                            <div className="mt-1 text-sm text-slate-900"><span className="font-semibold">Baptism date:</span> {baptismDate}</div>
                            <div className="mt-1 text-sm text-slate-900"><span className="font-semibold">Completed at:</span> {baptismData?.completed_at ?? '—'}</div>
                            <div className="mt-1 text-sm text-slate-900"><span className="font-semibold">Location:</span> {schedule?.location_text ?? '—'}</div>
                        </div>

                        <div>
                            <div className="text-xs font-semibold uppercase tracking-wide text-slate-600">Sponsor</div>
                            <div className="mt-1 text-sm text-slate-900"><span className="font-semibold">Name:</span> {sponsor?.name ?? '—'}</div>
                            <div className="mt-1 text-sm text-slate-900"><span className="font-semibold">Role:</span> {sponsor?.role ?? '—'}</div>
                            <div className="mt-1 text-sm text-slate-900"><span className="font-semibold">Parish:</span> {sponsor?.parish ?? '—'}</div>
                            {(sponsor?.phone || sponsor?.email) && (
                                <div className="mt-1 text-xs text-slate-600">
                                    {sponsor?.phone ?? '—'}
                                    {sponsor?.email ? ` • ${sponsor.email}` : ''}
                                </div>
                            )}
                        </div>
                    </div>

                    <div className="mt-10 grid gap-6 sm:grid-cols-2">
                        <div className="border-t border-slate-200 pt-4">
                            <div className="text-xs font-semibold uppercase tracking-wide text-slate-600">Issued by</div>
                            <div className="mt-1 text-sm text-slate-900"><span className="font-semibold">Clerk/Staff:</span> {issuedBy}</div>
                            <div className="mt-1 text-sm text-slate-900"><span className="font-semibold">Issued at:</span> {issuedAt}</div>
                        </div>

                        <div className="border-t border-slate-200 pt-4">
                            <div className="text-xs font-semibold uppercase tracking-wide text-slate-600">Signatures</div>
                            <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                                <div>
                                    <div className="text-xs uppercase tracking-wide text-slate-500">Child</div>
                                    <div className="mt-1 text-base font-semibold text-slate-900">{childName}</div>
                                </div>

                                <div>
                                    <div className="text-xs uppercase tracking-wide text-slate-500">Certificate No</div>
                                    <div className="mt-1 text-base font-semibold text-slate-900">{baptism?.certificate_no ?? '—'}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="mt-10 border-t border-slate-200 pt-4 text-xs text-slate-600">
                        Verify this certificate using the certificate number shown above.
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
