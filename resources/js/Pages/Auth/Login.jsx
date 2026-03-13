import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Login({ status, canResetPassword }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
    });

    const submit = (e) => {
        e.preventDefault();

        post(route('login'), {
            onFinish: () => reset('password'),
        });
    };

    return (
        <div className="min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900">
            <Head title="Sign in" />
            <div className="flex min-h-screen items-center justify-center px-4 py-8">
                <div className="grid w-full max-w-5xl grid-cols-1 overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-slate-200 md:grid-cols-[1fr,420px]">
                    <div className="relative hidden bg-gradient-to-br from-indigo-500 via-indigo-600 to-violet-600 p-10 text-white md:block">
                        <div className="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(255,255,255,0.15),transparent_45%)]" />
                        <div className="relative flex h-full flex-col justify-between">
                            <div>
                                <p className="text-xs uppercase tracking-[0.3em] text-indigo-100">
                                    Institution Portal
                                </p>
                                <h1 className="mt-4 text-3xl font-bold leading-tight">
                                    Welcome back to your secure workspace
                                </h1>
                                <p className="mt-3 max-w-md text-indigo-100">
                                    Access dashboards, manage posts, and stay connected with a
                                    clean, enterprise-grade experience.
                                </p>
                            </div>
                            <div className="rounded-xl bg-white/10 p-4 backdrop-blur">
                                <p className="text-sm font-semibold text-white">
                                    Need help?
                                </p>
                                <p className="text-xs text-indigo-100">
                                    Contact your administrator to reset credentials or manage access.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div className="relative bg-white p-8 md:p-10">
                        {status && (
                            <div className="mb-4 rounded-lg bg-green-50 p-3 text-sm text-green-700 ring-1 ring-green-100">
                                {status}
                            </div>
                        )}
                        <div className="space-y-2">
                            <p className="text-xs font-semibold uppercase tracking-[0.25em] text-slate-400">
                                Sign in
                            </p>
                            <h2 className="text-2xl font-semibold text-slate-900">
                                Secure access
                            </h2>
                            <p className="text-sm text-slate-500">
                                Use your institutional credentials to continue.
                            </p>
                        </div>

                        <form onSubmit={submit} className="mt-8 space-y-6">
                            <div className="space-y-2">
                                <InputLabel htmlFor="email" value="Email address" />
                                <TextInput
                                    id="email"
                                    type="email"
                                    name="email"
                                    value={data.email}
                                    className="mt-1 block w-full"
                                    autoComplete="username"
                                    isFocused={true}
                                    onChange={(e) => setData('email', e.target.value)}
                                    placeholder="you@institution.edu"
                                />
                                <InputError message={errors.email} className="mt-2" />
                            </div>

                            <div className="space-y-2">
                                <InputLabel htmlFor="password" value="Password" />
                                <TextInput
                                    id="password"
                                    type="password"
                                    name="password"
                                    value={data.password}
                                    className="mt-1 block w-full"
                                    autoComplete="current-password"
                                    onChange={(e) => setData('password', e.target.value)}
                                    placeholder="••••••••"
                                />
                                <InputError message={errors.password} className="mt-2" />
                            </div>

                            <div className="flex items-center justify-between text-sm">
                                {canResetPassword && (
                                    <Link
                                        href={route('password.request')}
                                        className="font-semibold text-indigo-600 hover:text-indigo-800"
                                    >
                                        Forgot password?
                                    </Link>
                                )}
                                <span className="text-slate-400">
                                    Registration disabled
                                </span>
                            </div>

                            <PrimaryButton
                                disabled={processing}
                                className="w-full justify-center bg-indigo-600 text-base font-semibold hover:bg-indigo-700"
                            >
                                Log in
                            </PrimaryButton>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    );
}
