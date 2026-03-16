import InputError from '@/Components/InputError';
import TextInput from '@/Components/TextInput';
import { useMemo, useState } from 'react';

export default function FloatingInput({
    id,
    label,
    value,
    onChange,
    error,
    hint,
    required = false,
    type = 'text',
    showPasswordToggle,
    className = '',
    inputClassName = '',
    ...props
}) {
    const isPasswordType = type === 'password';
    const canTogglePassword = useMemo(() => {
        if (!isPasswordType) return false;
        if (typeof showPasswordToggle === 'boolean') return showPasswordToggle;
        return true;
    }, [isPasswordType, showPasswordToggle]);

    const [isPasswordVisible, setIsPasswordVisible] = useState(false);
    const effectiveType = isPasswordType && canTogglePassword
        ? (isPasswordVisible ? 'text' : 'password')
        : type;

    const labelNode = (() => {
        const source = label ?? '';
        const withStar = required && !source.includes('*') ? `${source} *` : source;
        const starIndex = withStar.lastIndexOf('*');

        if (starIndex === -1) {
            return withStar;
        }

        const before = withStar.slice(0, starIndex).replace(/\s+$/, '');
        const after = withStar.slice(starIndex + 1);

        return (
            <>
                {before}
                <span className="ml-1 font-semibold text-red-600">*</span>
                {after}
            </>
        );
    })();

    return (
        <div className={`relative ${className}`}>
            <TextInput
                id={id}
                type={effectiveType}
                value={value}
                onChange={onChange}
                placeholder=" "
                className={`peer h-11 w-full rounded-lg border-slate-300 bg-white px-3 pt-5 text-sm text-slate-900 shadow-sm transition focus:border-indigo-500 focus:ring-indigo-500 ${canTogglePassword ? 'pr-11' : ''} ${inputClassName}`}
                {...props}
            />

            {canTogglePassword && (
                <button
                    type="button"
                    onClick={() => setIsPasswordVisible((v) => !v)}
                    className="absolute right-2 top-2.5 inline-flex h-7 w-7 items-center justify-center rounded-md text-slate-500 transition hover:bg-slate-100 hover:text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                    aria-label={isPasswordVisible ? 'Hide password' : 'Show password'}
                >
                    {isPasswordVisible ? (
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            strokeWidth="2"
                            strokeLinecap="round"
                            strokeLinejoin="round"
                            className="h-4 w-4"
                        >
                            <path d="M10.733 5.076A10.744 10.744 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68" />
                            <path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61" />
                            <line x1="2" y1="2" x2="22" y2="22" />
                            <path d="M14.12 14.12a3 3 0 1 1-4.24-4.24" />
                        </svg>
                    ) : (
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            strokeWidth="2"
                            strokeLinecap="round"
                            strokeLinejoin="round"
                            className="h-4 w-4"
                        >
                            <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z" />
                            <circle cx="12" cy="12" r="3" />
                        </svg>
                    )}
                </button>
            )}

            <label
                htmlFor={id}
                className="pointer-events-none absolute left-3 top-2.5 origin-[0] -translate-y-1 scale-75 text-xs font-semibold text-slate-500 transition-all peer-placeholder-shown:top-3.5 peer-placeholder-shown:translate-y-0 peer-placeholder-shown:scale-100 peer-placeholder-shown:text-sm peer-placeholder-shown:font-medium peer-focus:top-2.5 peer-focus:-translate-y-1 peer-focus:scale-75 peer-focus:text-xs peer-focus:font-semibold peer-focus:text-indigo-600"
            >
                {labelNode}
            </label>
            {hint && <p className="mt-1 text-xs text-slate-500">{hint}</p>}
            <InputError className="mt-2" message={error} />
        </div>
    );
}
