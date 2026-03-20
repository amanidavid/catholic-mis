import InputError from '@/Components/InputError';
import { useMemo } from 'react';

export default function FloatingTextarea({
    id,
    label,
    value,
    onChange,
    error,
    hint,
    required = false,
    className = '',
    textareaClassName = '',
    rows = 4,
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
            <textarea
                id={id}
                value={value}
                onChange={onChange}
                placeholder=" "
                rows={rows}
                className={`peer w-full resize-y rounded-lg border border-slate-300 bg-white px-3 pb-3 pt-7 text-sm text-slate-900 shadow-sm transition focus:border-indigo-500 focus:ring-indigo-500 ${textareaClassName}`}
                {...props}
            />

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
