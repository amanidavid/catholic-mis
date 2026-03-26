import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, usePage } from '@inertiajs/react';

export default function ErrorPage({ status = 500, title = 'Error', message = 'An unexpected error occurred.', reference = null }) {
    const { auth } = usePage().props;
    const isAuthed = !!auth?.user;

    const content = (
        <>
            <Head title={title} />

            <div className="mx-auto max-w-2xl">
                <div className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
                    <div className="flex items-start justify-between gap-4">
                        <div className="min-w-0">
                            <p className="text-xs font-semibold text-slate-500">Error {status}</p>
                            <h1 className="mt-1 text-xl font-semibold text-slate-900">{title}</h1>
                            <p className="mt-2 text-sm text-slate-600">{message}</p>

                            {reference ? (
                                <p className="mt-3 text-xs font-semibold text-slate-500">
                                    Reference: <span className="font-mono">{reference}</span>
                                </p>
                            ) : null}

                            <div className="mt-5 flex flex-wrap gap-2">
                                <Link
                                    href={route('dashboard')}
                                    className="inline-flex h-11 items-center justify-center rounded-lg bg-indigo-600 px-4 text-sm font-semibold text-white hover:bg-indigo-700"
                                >
                                    Go to Dashboard
                                </Link>
                                <button
                                    type="button"
                                    onClick={() => window.location.reload()}
                                    className="inline-flex h-11 items-center justify-center rounded-lg border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-800 hover:bg-slate-50"
                                >
                                    Reload
                                </button>
                            </div>
                        </div>

                        <div className="hidden shrink-0 sm:block">
                            <div className="flex h-12 w-12 items-center justify-center rounded-2xl bg-rose-50 text-rose-700 ring-1 ring-rose-200">
                                <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2" aria-hidden="true">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M12 9v4m0 4h.01M10.29 3.86l-7.1 12.3A2 2 0 005 19h14a2 2 0 001.81-2.84l-7.1-12.3a2 2 0 00-3.42 0z" />
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );

    if (isAuthed) {
        return <AuthenticatedLayout header={title}>{content}</AuthenticatedLayout>;
    }

    return <div className="min-h-screen bg-slate-50 px-4 py-10">{content}</div>;
}
