import InputError from '@/Components/InputError';
import FloatingInput from '@/Components/FloatingInput';
import PrimaryButton from '@/Components/PrimaryButton';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';

export default function ForgotPassword({ status }) {
    const { data, setData, post, processing, errors } = useForm({
        email: '',
    });

    const submit = (e) => {
        e.preventDefault();

        post(route('password.email'));
    };

    return (
        <GuestLayout
            title="Forgot Password"
            subtitle="Enter your email to receive a password reset link"
        >
            <Head title="Forgot Password" />

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
                    required
                    isFocused={true}
                />

                <div className="flex items-center justify-between gap-3">
                    <Link
                        href={route('login')}
                        className="text-sm font-semibold text-slate-600 hover:text-slate-900"
                    >
                        Back to login
                    </Link>

                    <PrimaryButton disabled={processing} className="bg-indigo-700 hover:bg-indigo-800">
                        Send Reset Link
                    </PrimaryButton>
                </div>
            </form>
        </GuestLayout>
    );
}
