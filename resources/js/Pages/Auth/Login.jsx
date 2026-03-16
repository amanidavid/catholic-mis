import FloatingInput from '@/Components/FloatingInput';
import PrimaryButton from '@/Components/PrimaryButton';
import GuestLayout from '@/Layouts/GuestLayout';
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
        <GuestLayout
            title="Login"
            subtitle="Provide your email and password to login"
        >
            <Head title="Login" />

            {status && (
                <div className="mb-4 rounded-lg bg-green-50 p-3 text-sm text-green-700 ring-1 ring-green-100">
                    {status}
                </div>
            )}

            <form onSubmit={submit} className="space-y-5">
                <FloatingInput
                    id="email"
                    label="Email Address"
                    type="email"
                    name="email"
                    value={data.email}
                    onChange={(e) => setData('email', e.target.value)}
                    error={errors.email}
                    autoComplete="username"
                    required
                    isFocused={true}
                />

                <FloatingInput
                    id="password"
                    label="Password"
                    type="password"
                    name="password"
                    value={data.password}
                    onChange={(e) => setData('password', e.target.value)}
                    error={errors.password}
                    autoComplete="current-password"
                    required
                />

                {canResetPassword && (
                    <div className="flex justify-end">
                        <Link
                            href={route('password.request')}
                            className="text-sm font-semibold text-indigo-700 hover:text-indigo-900"
                        >
                            Forgot Password?
                        </Link>
                    </div>
                )}

                <PrimaryButton
                    disabled={processing}
                    className="w-full justify-center bg-indigo-700 text-base font-semibold hover:bg-indigo-800"
                >
                    Login
                </PrimaryButton>
            </form>
        </GuestLayout>
    );
}
