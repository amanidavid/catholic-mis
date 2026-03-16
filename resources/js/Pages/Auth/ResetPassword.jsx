import FloatingInput from '@/Components/FloatingInput';
import PrimaryButton from '@/Components/PrimaryButton';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';

export default function ResetPassword({ token, email }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        token: token,
        email: email,
        password: '',
        password_confirmation: '',
    });

    const submit = (e) => {
        e.preventDefault();

        post(route('password.store'), {
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    return (
        <GuestLayout
            title="Reset Password"
            subtitle="Choose a strong new password for your account"
        >
            <Head title="Reset Password" />

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
                />

                <FloatingInput
                    id="password"
                    label="Password"
                    type="password"
                    name="password"
                    value={data.password}
                    onChange={(e) => setData('password', e.target.value)}
                    error={errors.password}
                    autoComplete="new-password"
                    required
                    isFocused={true}
                />

                <FloatingInput
                    id="password_confirmation"
                    label="Confirm Password"
                    type="password"
                    name="password_confirmation"
                    value={data.password_confirmation}
                    onChange={(e) => setData('password_confirmation', e.target.value)}
                    error={errors.password_confirmation}
                    autoComplete="new-password"
                    required
                />

                <div className="flex items-center justify-between gap-3">
                    <Link
                        href={route('login')}
                        className="text-sm font-semibold text-slate-600 hover:text-slate-900"
                    >
                        Back to login
                    </Link>

                    <PrimaryButton disabled={processing} className="bg-indigo-700 hover:bg-indigo-800">
                        Reset Password
                    </PrimaryButton>
                </div>
            </form>
        </GuestLayout>
    );
}
