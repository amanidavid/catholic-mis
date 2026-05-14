import DeceasedRegisterForm from './Form';

export default function DeceasedRegisterCreate({ defaults }) {
    return (
        <DeceasedRegisterForm
            mode="create"
            title="New Deceased Record"
            subtitle="Capture death and funeral information."
            submitLabel="Save"
            submitRoute={route('pastoral.deceased-register.store')}
            method="post"
            initial={defaults}
        />
    );
}
