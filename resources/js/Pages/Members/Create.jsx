import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import FloatingInput from '@/Components/FloatingInput';
import FloatingSelect from '@/Components/FloatingSelect';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import SearchableFamilySelect from '@/Components/SearchableFamilySelect';
import SearchableFamilyRelationshipSelect from '@/Components/SearchableFamilyRelationshipSelect';
import SearchableJumuiyaSelect from '@/Components/SearchableJumuiyaSelect';
import SearchableZoneSelect from '@/Components/SearchableZoneSelect';
import { Head, Link, useForm } from '@inertiajs/react';
import { useEffect, useMemo } from 'react';

export default function MembersCreate({ defaults }) {
    const defaultZone = defaults?.zone_uuid ?? '';
    const defaultZoneName = defaults?.zone_name ?? '';
    const defaultJumuiya = defaults?.jumuiya_uuid ?? '';
    const defaultJumuiyaName = defaults?.jumuiya_name ?? '';
    const defaultFamily = defaults?.family_uuid ?? '';

    const isScoped = useMemo(() => !!defaultJumuiya && !defaults?.jumuiya_uuid_from_query, [defaultJumuiya]);

    const {
        data,
        setData,
        post,
        processing,
        errors,
        reset,
    } = useForm({
        zone_uuid: defaultZone,
        jumuiya_uuid: defaultJumuiya,
        family_uuid: defaultFamily,
        family_relationship_uuid: '',
        is_head_of_family: false,
        first_name: '',
        middle_name: '',
        last_name: '',
        gender: '',
        birth_date: '',
        phone: '',
        email: '',
        national_id: '',
        marital_status: '',
    });

    useEffect(() => {
        return () => reset();
    }, []);

    const friendlyErrors = useMemo(() => {
        const e = errors ?? {};

        const mapMsg = (field, msg) => {
            if (typeof msg !== 'string') return msg;
            const m = msg.trim();

            if (field === 'phone' && /taken/i.test(m)) return 'Phone number already exists.';
            if (field === 'phone' && /format is invalid/i.test(m)) return 'Phone number format is invalid.';

            if (field === 'national_id' && /taken/i.test(m)) return 'National ID already exists.';
            if (field === 'national_id' && /format is invalid/i.test(m)) return 'National ID must be 20 digits.';

            if (field === 'jumuiya_uuid' && /required/i.test(m)) return 'Please select a Christian Community.';
            if (field === 'family_uuid' && /required/i.test(m)) return 'Please select a family.';

            if (field === 'first_name' && /required/i.test(m)) return 'First name is required.';
            if (field === 'last_name' && /required/i.test(m)) return 'Last name is required.';

            return m;
        };

        return Object.fromEntries(Object.entries(e).map(([field, msg]) => [field, mapMsg(field, msg)]));
    }, [errors]);

    const canSubmit = useMemo(() => {
        return !!data.jumuiya_uuid && !!data.family_uuid && !!data.first_name && !!data.last_name;
    }, [data]);

    const submit = (e) => {
        e.preventDefault();
        post(route('members.store'), { preserveScroll: true });
    };

    return (
        <AuthenticatedLayout>
            <Head title="Add Member" />

            <div className="mx-auto max-w-4xl space-y-6">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h1 className="text-xl font-semibold text-slate-900">Add member</h1>
                        <p className="mt-1 text-sm text-slate-500">Create a new member record.</p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Link
                            href={route('members.index')}
                            className="inline-flex h-11 items-center rounded-lg border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                        >
                            Back
                        </Link>
                    </div>
                </div>

                <form onSubmit={submit} className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70 space-y-6">
                    {friendlyErrors && Object.keys(friendlyErrors).length > 0 && (
                        <div className="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                            <div className="font-semibold">Please fix the following:</div>
                            <ul className="mt-2 list-disc pl-5">
                                {Object.entries(friendlyErrors).map(([field, msg]) => (
                                    <li key={field}>{msg}</li>
                                ))}
                            </ul>
                        </div>
                    )}

                    <div>
                        <div className="text-sm font-semibold text-slate-700">Family & placement</div>
                        <div className="mt-3 grid gap-4 md:grid-cols-2">
                            {!isScoped && (
                                <SearchableZoneSelect
                                    id="member_zone_uuid"
                                    label="Zone"
                                    value={data.zone_uuid}
                                    onChange={(uuid) => {
                                        setData('zone_uuid', uuid);
                                        setData('jumuiya_uuid', '');
                                        setData('family_uuid', '');
                                        setData('is_head_of_family', false);
                                    }}
                                    error={friendlyErrors.zone_uuid}
                                />
                            )}

                            {isScoped && (
                                <div className="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                                    <div className="text-xs font-semibold uppercase tracking-wide text-slate-500">Zone</div>
                                    <div className="mt-1 text-sm font-semibold text-slate-900 break-words">{defaultZoneName || '-'}</div>
                                </div>
                            )}

                            {!isScoped && (
                                <SearchableJumuiyaSelect
                                    id="member_jumuiya_uuid"
                                    label="Christian Community"
                                    value={data.jumuiya_uuid}
                                    onChange={(uuid) => {
                                        setData('jumuiya_uuid', uuid);
                                        setData('family_uuid', '');
                                        setData('is_head_of_family', false);
                                    }}
                                    zoneUuid={data.zone_uuid}
                                    disabled={!data.zone_uuid}
                                    error={errors.jumuiya_uuid}
                                />
                            )}

                            {isScoped && (
                                <div className="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                                    <div className="text-xs font-semibold uppercase tracking-wide text-slate-500">Christian Community</div>
                                    <div className="mt-1 text-sm font-semibold text-slate-900 break-words">{defaultJumuiyaName || '-'}</div>
                                </div>
                            )}

                            <SearchableFamilySelect
                                id="member_family_uuid"
                                label="Family"
                                value={data.family_uuid}
                                onChange={(uuid) => {
                                    setData('family_uuid', uuid);
                                    if (!uuid) {
                                        setData('is_head_of_family', false);
                                    }
                                }}
                                jumuiyaUuid={data.jumuiya_uuid}
                                disabled={!data.jumuiya_uuid}
                                error={friendlyErrors.family_uuid}
                            />
                        </div>

                        <div className="mt-4">
                            <label className="flex items-center gap-2 text-sm font-semibold text-slate-700">
                                <input
                                    type="checkbox"
                                    checked={!!data.is_head_of_family}
                                    onChange={(e) => setData('is_head_of_family', e.target.checked)}
                                    className="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                    disabled={!data.family_uuid}
                                />
                                Head of family
                            </label>
                            {errors.is_head_of_family && (
                                <div className="mt-1 text-sm text-rose-600">{errors.is_head_of_family}</div>
                            )}
                        </div>
                    </div>

                    <div>
                        <div className="text-sm font-semibold text-slate-700">Personal details</div>
                        <div className="mt-3 grid gap-4 md:grid-cols-2">
                            <FloatingInput
                                id="first_name"
                                label="First name"
                                required
                                value={data.first_name}
                                onChange={(e) => setData('first_name', e.target.value)}
                                error={friendlyErrors.first_name}
                            />
                            <FloatingInput
                                id="last_name"
                                label="Last name"
                                required
                                value={data.last_name}
                                onChange={(e) => setData('last_name', e.target.value)}
                                error={friendlyErrors.last_name}
                            />
                            <FloatingInput
                                id="middle_name"
                                label="Middle name"
                                value={data.middle_name}
                                onChange={(e) => setData('middle_name', e.target.value)}
                                error={friendlyErrors.middle_name}
                            />
                            <FloatingSelect
                                id="gender"
                                label="Gender"
                                value={data.gender}
                                onChange={(e) => setData('gender', e.target.value)}
                                error={friendlyErrors.gender}
                            >
                                <option value="">Select gender</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                            </FloatingSelect>
                            <SearchableFamilyRelationshipSelect
                                id="family_relationship_uuid"
                                label="Family relationship"
                                value={data.family_relationship_uuid}
                                onChange={(uuid) => setData('family_relationship_uuid', uuid)}
                                error={friendlyErrors.family_relationship_uuid}
                            />
                            <FloatingInput
                                id="birth_date"
                                label="Birth date"
                                type="date"
                                value={data.birth_date}
                                onChange={(e) => setData('birth_date', e.target.value)}
                                error={friendlyErrors.birth_date}
                            />
                        </div>
                    </div>

                    <div>
                        <div className="text-sm font-semibold text-slate-700">Contacts</div>
                        <div className="mt-3 grid gap-4 md:grid-cols-2">
                            <FloatingInput
                                id="phone"
                                label="Phone"
                                value={data.phone}
                                onChange={(e) => setData('phone', e.target.value)}
                                error={friendlyErrors.phone}
                            />
                            <FloatingInput
                                id="email"
                                label="Email"
                                type="email"
                                value={data.email}
                                onChange={(e) => setData('email', e.target.value)}
                                error={friendlyErrors.email}
                            />
                        </div>
                    </div>

                    <div>
                        <div className="text-sm font-semibold text-slate-700">Other</div>
                        <div className="mt-3 grid gap-4 md:grid-cols-2">
                            <FloatingInput
                                id="national_id"
                                label="National ID"
                                hint="Example: 19991231234567890123"
                                value={data.national_id}
                                onChange={(e) => setData('national_id', e.target.value)}
                                error={friendlyErrors.national_id}
                            />
                            <FloatingSelect
                                id="marital_status"
                                label="Marital status"
                                value={data.marital_status}
                                onChange={(e) => setData('marital_status', e.target.value)}
                                error={friendlyErrors.marital_status}
                            >
                                <option value="">Select</option>
                                <option value="single">Single</option>
                                <option value="married">Married</option>
                                <option value="widowed">Widowed</option>
                                <option value="divorced">Divorced</option>
                            </FloatingSelect>
                        </div>
                    </div>

                    <div className="flex flex-wrap items-center justify-end gap-2">
                        <Link
                            href={route('members.index')}
                            className="inline-flex h-11 items-center rounded-lg border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                        >
                            Cancel
                        </Link>
                        <SecondaryButton type="reset" disabled={processing} className="h-11 rounded-lg text-sm font-semibold normal-case tracking-normal">
                            Reset
                        </SecondaryButton>
                        <PrimaryButton
                            type="submit"
                            disabled={processing || !canSubmit}
                            className="h-11 rounded-lg text-sm font-semibold normal-case tracking-normal bg-indigo-600 text-white hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-800"
                        >
                            {processing ? 'Saving...' : 'Save Member'}
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </AuthenticatedLayout>
    );
}
