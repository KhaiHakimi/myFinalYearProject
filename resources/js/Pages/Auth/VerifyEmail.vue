<script setup>
    import { computed } from 'vue'
    import GuestLayout from '@/Layouts/GuestLayout.vue'
    import PrimaryButton from '@/Components/PrimaryButton.vue'
    import InputError from '@/Components/InputError.vue'
    import InputLabel from '@/Components/InputLabel.vue'
    import TextInput from '@/Components/TextInput.vue'
    import { Head, Link, useForm } from '@inertiajs/vue3'

    const props = defineProps({
        status: {
            type: String,
        },
    })

    const form = useForm({
        code: '',
    })

    const submit = () => {
        form.post(route('verification.verify'))
    }

    const resendForm = useForm({})

    const resend = () => {
        resendForm.post(route('verification.send'))
    }

    const verificationLinkSent = computed(
        () => props.status === 'verification-link-sent',
    )
</script>

<template>
    <GuestLayout>
        <Head title="Email Verification" />

        <div class="mb-4 text-sm text-gray-600">
            Thanks for signing up! Before getting started, could you verify your
            email address by entering the 6-digit code we just emailed to you? If you
            didn't receive the email, we will gladly send you another.
        </div>

        <div
            class="mb-4 text-sm font-medium text-green-600"
            v-if="verificationLinkSent"
        >
            A new verification code has been sent to the email address you
            provided during registration.
        </div>

        <form @submit.prevent="submit">
            <div>
                <InputLabel for="code" value="Verification Code" />

                <TextInput
                    id="code"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.code"
                    required
                    autofocus
                    autocomplete="one-time-code"
                />

                <InputError class="mt-2" :message="form.errors.code" />
            </div>

            <div class="mt-4 flex items-center justify-between">
                <PrimaryButton
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Verify Email
                </PrimaryButton>

                <div>
                    <button
                        type="button"
                        @click="resend"
                        class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 mr-4"
                        :disabled="resendForm.processing"
                    >
                        Resend Code
                    </button>

                    <Link
                        :href="route('logout')"
                        method="post"
                        as="button"
                        class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                        >Log Out</Link
                    >
                </div>
            </div>
        </form>
    </GuestLayout>
</template>
