import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import FloatingInput from '@/Components/FloatingInput';
import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import Spinner from '@/Components/Spinner';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import { useSetupStore } from '@/stores/setupStore';
import { useEffect, useMemo, useState } from 'react';

export default function SetupIndex({ diocese, parish }) {
    const { flash } = usePage().props;
    const { activeTab, setActiveTab } = useSetupStore();

    useEffect(() => {
        setActiveTab('structure');
    }, [setActiveTab]);

    const dioceseData = useMemo(() => diocese?.data ?? diocese ?? null, [diocese]);
    const parishData = useMemo(() => parish?.data ?? parish ?? null, [parish]);

    const [successOpen, setSuccessOpen] = useState(false);
    useEffect(() => {
        if (flash?.success) {
            setSuccessOpen(true);
        }
    }, [flash?.success]);

    const { data, setData, post, processing, errors } = useForm({
        diocese: {
            name: dioceseData?.name ?? '',
            archbishop_name: dioceseData?.archbishop_name ?? '',
            established_year: dioceseData?.established_year ?? '',
            address: dioceseData?.address ?? '',
            phone: dioceseData?.phone ?? '',
            email: dioceseData?.email ?? '',
            website: dioceseData?.website ?? '',
            country: dioceseData?.country ?? '',
        },
        parish: {
            name: parishData?.name ?? '',
            code: parishData?.code ?? '',
            patron_saint: parishData?.patron_saint ?? '',
            established_year: parishData?.established_year ?? '',
            address: parishData?.address ?? '',
            phone: parishData?.phone ?? '',
            email: parishData?.email ?? '',
        },
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('setup.store'));
    };

    const setNested = (path, value) => {
        const [root, key] = path.split('.');
        setData(root, { ...data[root], [key]: value });
    };

    return (
        <AuthenticatedLayout header="Parish">
            <Head title="Parish" />

            <Modal show={successOpen} onClose={() => setSuccessOpen(false)} maxWidth="md">
                <div className="p-6">
                    <div className="flex items-start gap-4">
                        <div className="flex h-11 w-11 items-center justify-center rounded-full bg-emerald-50 text-emerald-600 ring-1 ring-emerald-100">
                            <svg className="h-6 w-6" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <div className="flex-1">
                            <h3 className="text-lg font-semibold text-slate-900">Saved</h3>
                            <p className="mt-1 text-sm text-slate-600">
                                Your configuration has been saved successfully.
                            </p>
                        </div>
                    </div>
                    <div className="mt-6 flex justify-end">
                        <button
                            type="button"
                            onClick={() => setSuccessOpen(false)}
                            className="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700"
                        >
                            Continue
                        </button>
                    </div>
                </div>
            </Modal>

            <form onSubmit={submit} className="space-y-8">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h1 className="text-xl font-semibold text-slate-900">Parish</h1>
                        <p className="mt-1 text-sm text-slate-500">
                            Set up diocese and parish details.
                        </p>
                        <p className="mt-2 text-xs text-slate-500">
                            Fields marked <span className="font-semibold text-red-600">*</span> are required.
                        </p>
                    </div>
                </div>

                <>
                    <section className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
                        <h2 className="text-lg font-semibold text-slate-900">Diocese</h2>
                        <div className="mt-4 grid gap-4 md:grid-cols-2">
                            <FloatingInput
                                id="diocese_name"
                                label="Diocese name"
                                required
                                value={data.diocese.name}
                                onChange={(e) => setNested('diocese.name', e.target.value)}
                                error={errors['diocese.name']}
                            />
                            <FloatingInput
                                id="archbishop_name"
                                label="Bishop/Archbishop"
                                required
                                value={data.diocese.archbishop_name}
                                onChange={(e) => setNested('diocese.archbishop_name', e.target.value)}
                                error={errors['diocese.archbishop_name']}
                            />
                            <FloatingInput
                                id="diocese_established_year"
                                label="Established year"
                                type="number"
                                min={1800}
                                max={2100}
                                required
                                value={data.diocese.established_year}
                                onChange={(e) => setNested('diocese.established_year', e.target.value)}
                                error={errors['diocese.established_year']}
                            />
                            <FloatingInput
                                id="diocese_phone"
                                label="Phone"
                                type="tel"
                                hint="Format: 07XXXXXXXX, 06XXXXXXXX, +2557XXXXXXXX"
                                required
                                value={data.diocese.phone}
                                onChange={(e) => setNested('diocese.phone', e.target.value)}
                                error={errors['diocese.phone']}
                            />
                            <FloatingInput
                                id="diocese_email"
                                label="Email"
                                type="email"
                                required
                                value={data.diocese.email}
                                onChange={(e) => setNested('diocese.email', e.target.value)}
                                error={errors['diocese.email']}
                            />
                            <FloatingInput
                                id="diocese_website"
                                label="Website"
                                type="url"
                                hint="Example: https://example.org"
                                value={data.diocese.website}
                                onChange={(e) => setNested('diocese.website', e.target.value)}
                                error={errors['diocese.website']}
                            />
                            <FloatingInput
                                id="diocese_country"
                                label="Country"
                                required
                                value={data.diocese.country}
                                onChange={(e) => setNested('diocese.country', e.target.value)}
                                error={errors['diocese.country']}
                            />
                            <FloatingInput
                                id="diocese_address"
                                label="Address"
                                required
                                value={data.diocese.address}
                                onChange={(e) => setNested('diocese.address', e.target.value)}
                                error={errors['diocese.address']}
                                inputClassName="h-12"
                            />
                        </div>
                    </section>

                    <section className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
                        <h2 className="text-lg font-semibold text-slate-900">Parish</h2>
                        <div className="mt-4 grid gap-4 md:grid-cols-2">
                            <FloatingInput
                                id="parish_name"
                                label="Parish name"
                                required
                                value={data.parish.name}
                                onChange={(e) => setNested('parish.name', e.target.value)}
                                error={errors['parish.name']}
                            />
                            <FloatingInput
                                id="parish_code"
                                label="Code"
                                value={data.parish.code}
                                onChange={(e) => setNested('parish.code', e.target.value)}
                                error={errors['parish.code']}
                            />
                            <FloatingInput
                                id="parish_patron"
                                label="Patron saint"
                                required
                                value={data.parish.patron_saint}
                                onChange={(e) => setNested('parish.patron_saint', e.target.value)}
                                error={errors['parish.patron_saint']}
                            />
                            <FloatingInput
                                id="parish_established_year"
                                label="Established year"
                                type="number"
                                min={1800}
                                max={2100}
                                required
                                value={data.parish.established_year}
                                onChange={(e) => setNested('parish.established_year', e.target.value)}
                                error={errors['parish.established_year']}
                            />
                            <FloatingInput
                                id="parish_phone"
                                label="Phone"
                                type="tel"
                                hint="Format: 07XXXXXXXX, 06XXXXXXXX, +2557XXXXXXXX"
                                required
                                value={data.parish.phone}
                                onChange={(e) => setNested('parish.phone', e.target.value)}
                                error={errors['parish.phone']}
                            />
                            <FloatingInput
                                id="parish_email"
                                label="Email"
                                type="email"
                                required
                                value={data.parish.email}
                                onChange={(e) => setNested('parish.email', e.target.value)}
                                error={errors['parish.email']}
                            />
                            <FloatingInput
                                id="parish_address"
                                label="Address"
                                required
                                value={data.parish.address}
                                onChange={(e) => setNested('parish.address', e.target.value)}
                                error={errors['parish.address']}
                                inputClassName="h-12"
                            />
                        </div>

                        <div className="mt-6 flex justify-end">
                            <PrimaryButton disabled={processing} className="gap-2">
                                {processing && <Spinner size="sm" className="text-white" />}
                                <span>Save</span>
                            </PrimaryButton>
                        </div>
                    </section>
                </>
            </form>
        </AuthenticatedLayout>
    );
}

function TabButton({ active, onClick, label, disabled = false }) {
    return (
        <button
            type="button"
            onClick={disabled ? undefined : onClick}
            disabled={disabled}
            className={`rounded-lg px-3 py-2 text-sm font-semibold transition ${disabled
                ? 'cursor-not-allowed text-slate-400'
                : active
                    ? 'bg-indigo-600 text-white'
                    : 'text-slate-700 hover:bg-slate-50'
                }`}
        >
            {label}
        </button>
    );
}
