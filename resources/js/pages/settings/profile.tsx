import { send } from '@/routes/verification';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Transition } from '@headlessui/react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { CameraIcon, Trash2Icon } from 'lucide-react';
import { type ChangeEvent, type FormEvent, useRef, useState } from 'react';

import HeadingSmall from '@/components/heading-small';
import InputError from '@/components/input-error';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useInitials } from '@/hooks/use-initials';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { edit } from '@/routes/profile';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Configuración de perfil',
        href: edit().url,
    },
];

export default function Profile({
    mustVerifyEmail,
    status,
}: {
    mustVerifyEmail: boolean;
    status?: string;
}) {
    const { auth } = usePage<SharedData>().props;
    const getInitials = useInitials();
    const fileInputRef = useRef<HTMLInputElement>(null);

    const [processing, setProcessing] = useState(false);
    const [recentlySuccessful, setRecentlySuccessful] = useState(false);
    const [avatarPreview, setAvatarPreview] = useState<string | null>(null);
    const [selectedFile, setSelectedFile] = useState<File | null>(null);
    const [removeAvatar, setRemoveAvatar] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});

    const currentAvatarUrl = removeAvatar ? null : (avatarPreview ?? auth.user.avatar ?? null);

    const handleAvatarChange = (e: ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (!file) return;

        setSelectedFile(file);
        setRemoveAvatar(false);
        setAvatarPreview(URL.createObjectURL(file));
    };

    const handleRemoveAvatar = () => {
        setSelectedFile(null);
        setAvatarPreview(null);
        setRemoveAvatar(true);
        if (fileInputRef.current) {
            fileInputRef.current.value = '';
        }
    };

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();

        const formData = new FormData(e.target as HTMLFormElement);

        if (selectedFile) {
            formData.set('avatar', selectedFile);
        }

        if (removeAvatar) {
            formData.set('remove_avatar', '1');
        }

        router.post(edit().url, formData, {
            preserveScroll: true,
            forceFormData: true,
            onStart: () => setProcessing(true),
            onSuccess: () => {
                setRecentlySuccessful(true);
                setSelectedFile(null);
                setAvatarPreview(null);
                setRemoveAvatar(false);
                setTimeout(() => setRecentlySuccessful(false), 2000);
            },
            onError: (errs) => setErrors(errs),
            onFinish: () => setProcessing(false),
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Configuración de perfil" />

            <SettingsLayout>
                <div className="space-y-6">
                    <HeadingSmall
                        title="Información de perfil"
                        description="Actualiza tu foto de perfil, nombre y correo electrónico"
                    />

                    <form onSubmit={handleSubmit} className="space-y-6">
                        {/* Avatar Upload */}
                        <div className="flex items-center gap-6">
                            <div className="relative">
                                <Avatar className="size-20 border-2 border-muted">
                                    <AvatarImage
                                        src={currentAvatarUrl ?? undefined}
                                        alt={auth.user.name}
                                    />
                                    <AvatarFallback className="text-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white">
                                        {getInitials(auth.user.name)}
                                    </AvatarFallback>
                                </Avatar>
                                <button
                                    type="button"
                                    onClick={() => fileInputRef.current?.click()}
                                    className="absolute -bottom-1 -right-1 flex size-8 items-center justify-center rounded-full border-2 border-background bg-primary text-primary-foreground shadow-sm transition-colors hover:bg-primary/90"
                                >
                                    <CameraIcon className="size-4" />
                                </button>
                            </div>
                            <div className="space-y-1">
                                <p className="text-sm font-medium">Foto de perfil</p>
                                <p className="text-xs text-muted-foreground">
                                    JPG, PNG, GIF o WebP. Máximo 5MB.
                                </p>
                                <div className="flex gap-2">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        onClick={() => fileInputRef.current?.click()}
                                    >
                                        Cambiar foto
                                    </Button>
                                    {(auth.user.avatar || avatarPreview) && !removeAvatar && (
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="sm"
                                            className="text-destructive hover:text-destructive"
                                            onClick={handleRemoveAvatar}
                                        >
                                            <Trash2Icon className="mr-1 size-3.5" />
                                            Eliminar
                                        </Button>
                                    )}
                                </div>
                                <InputError message={errors.avatar} />
                            </div>
                            <input
                                ref={fileInputRef}
                                type="file"
                                accept="image/jpeg,image/png,image/gif,image/webp"
                                onChange={handleAvatarChange}
                                className="hidden"
                            />
                        </div>

                        {/* Name */}
                        <div className="grid gap-2">
                            <Label htmlFor="name">Nombre</Label>
                            <Input
                                id="name"
                                className="mt-1 block w-full"
                                defaultValue={auth.user.name}
                                name="name"
                                required
                                autoComplete="name"
                                placeholder="Nombre completo"
                            />
                            <InputError className="mt-2" message={errors.name} />
                        </div>

                        {/* Email */}
                        <div className="grid gap-2">
                            <Label htmlFor="email">Correo electrónico</Label>
                            <Input
                                id="email"
                                type="email"
                                className="mt-1 block w-full"
                                defaultValue={auth.user.email}
                                name="email"
                                required
                                autoComplete="username"
                                placeholder="Correo electrónico"
                            />
                            <InputError className="mt-2" message={errors.email} />
                        </div>

                        {mustVerifyEmail && auth.user.email_verified_at === null && (
                            <div>
                                <p className="-mt-4 text-sm text-muted-foreground">
                                    Tu correo electrónico no está verificado.{' '}
                                    <Link
                                        href={send()}
                                        as="button"
                                        className="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                                    >
                                        Haz clic aquí para reenviar el correo de verificación.
                                    </Link>
                                </p>

                                {status === 'verification-link-sent' && (
                                    <div className="mt-2 text-sm font-medium text-green-600">
                                        Se ha enviado un nuevo enlace de verificación a tu correo
                                        electrónico.
                                    </div>
                                )}
                            </div>
                        )}

                        <div className="flex items-center gap-4">
                            <Button disabled={processing} data-test="update-profile-button">
                                Guardar
                            </Button>

                            <Transition
                                show={recentlySuccessful}
                                enter="transition ease-in-out"
                                enterFrom="opacity-0"
                                leave="transition ease-in-out"
                                leaveTo="opacity-0"
                            >
                                <p className="text-sm text-neutral-600">Guardado</p>
                            </Transition>
                        </div>
                    </form>
                </div>

                {/* <DeleteUser /> */}
            </SettingsLayout>
        </AppLayout>
    );
}
