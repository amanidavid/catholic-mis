import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';

export default function Dashboard() {
    return (
        <AuthenticatedLayout
            header="Dashboard"
        >
            <Head title="Dashboard" />

            <div className="space-y-8">
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <StatCard title="Status" value="Online" subtitle="You are logged in" />
                    <StatCard title="Setup" value="Pending" subtitle="Complete Parish" />
                    <StatCard title="Active Session" value="Online" subtitle="You are logged in" />
                    <StatCard title="Next Action" value="Profile" subtitle="Review your details" />
                </div>

                <div className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
                    <div className="flex items-center justify-between">
                        <div>
                            <h3 className="text-lg font-semibold text-slate-900">
                                Quick Actions
                            </h3>
                            <p className="text-sm text-slate-500">
                                Common actions you can take
                            </p>
                        </div>
                        <Link
                            href={route('profile.edit')}
                            className="text-sm font-semibold text-indigo-600 hover:text-indigo-800"
                        >
                            Profile
                        </Link>
                    </div>

                    <div className="mt-4 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                        <Link
                            href={route('profile.edit')}
                            className="flex items-center justify-between rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-800 hover:border-indigo-200 hover:bg-indigo-50"
                        >
                            Profile
                            <span className="text-xs text-indigo-600">Edit</span>
                        </Link>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

function StatCard({ title, value, subtitle }) {
    return (
        <div className="rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200/70">
            <p className="text-sm text-slate-500">{title}</p>
            <p className="mt-2 text-3xl font-bold text-slate-900">{value}</p>
            <p className="text-xs text-slate-400">{subtitle}</p>
        </div>
    );
}
