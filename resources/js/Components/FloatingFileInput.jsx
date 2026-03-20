import InputError from '@/Components/InputError';
import { useMemo } from 'react';

export default function FloatingFileInput({
    id,
    label,
    onChange,
    error,
    hint,
    required = false,
    className = '',
    inputClassName = '',
    accept,
    disabled,
    ...props
}) {
    const labelNode = useMemo(() => {
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
    }, [label, required]);

    return (
        <div className={`relative ${className}`}>
            <input
                id={id}
                type="file"
                accept={accept}
                disabled={disabled}
                onChange={onChange}
                className={`peer h-11 w-full cursor-pointer rounded-lg border border-slate-300 bg-white px-3 pt-5 text-sm text-slate-900 shadow-sm transition file:mr-3 file:rounded-md file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-xs file:font-semibold file:text-slate-700 hover:file:bg-slate-200 focus:border-indigo-500 focus:ring-indigo-500 disabled:cursor-not-allowed disabled:opacity-60 ${inputClassName}`}
                {...props}
            />

            <label
                htmlFor={id}
                className="pointer-events-none absolute left-3 top-2.5 origin-[0] -translate-y-1 scale-75 text-xs font-semibold text-slate-500 transition-all peer-focus:top-2.5 peer-focus:-translate-y-1 peer-focus:scale-75 peer-focus:text-xs peer-focus:font-semibold peer-focus:text-indigo-600"
            >
                {labelNode}
            </label>

            {hint && <p className="mt-1 text-xs text-slate-500">{hint}</p>}
            <InputError className="mt-2" message={error} />
        </div>
    );
}
