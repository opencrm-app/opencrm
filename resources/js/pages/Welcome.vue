<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Clock, Shield, BarChart3, Users, Zap, CheckCircle } from 'lucide-vue-next';
import AppLogo from '@/components/AppLogo.vue';

defineProps<{
    canLogin?: boolean;
    canRegister?: boolean;
}>();
</script>

<template>
    <Head title="Offline Time Tracker - Track Every Minute" />

    <div class="min-h-screen bg-background text-foreground flex flex-col">
        <!-- Header -->
        <header class="border-b bg-card/50 backdrop-blur-sm sticky top-0 z-50">
            <div class="container mx-auto px-4 h-16 flex items-center justify-between">
                <div class="flex items-center gap-2 font-bold text-xl text-primary">
                    <AppLogo />
                </div>
                <div class="flex items-center gap-4">
                    <Link href="/login">
                        <Button variant="ghost">Log in</Button>
                    </Link>
                    <Link href="/register">
                        <Button>Get Started</Button>
                    </Link>
                </div>
            </div>
        </header>

        <!-- Hero Section -->
        <main class="flex-1">
            <section class="py-20 md:py-32 px-4 text-center space-y-8 bg-gradient-to-b from-background to-muted/50">
                <div class="container mx-auto max-w-4xl space-y-6">
                    <Badge variant="outline" class="w-fit mx-auto animate-fade-in">
                        Version 1.0 Now Available
                    </Badge>
                    <h1 class="text-4xl md:text-6xl font-bold tracking-tight bg-gradient-to-r from-primary to-blue-600 bg-clip-text text-transparent pb-2">
                        Track Your Offline Work With Ease
                    </h1>
                    <p class="text-xl text-muted-foreground max-w-2xl mx-auto">
                        Don't let your meetings and offline tasks go unnoticed. Manually log your time, generate reports, and verify your actual working hours seamlessly.
                    </p>
                    <div class="flex flex-col sm:flex-row items-center justify-center gap-4 pt-4">
                        <Link href="/register">
                            <Button size="lg" class="w-full sm:w-auto text-lg h-12 px-8">
                                Start Tracking Now
                            </Button>
                        </Link>
                        <Link href="/login">
                            <Button variant="outline" size="lg" class="w-full sm:w-auto text-lg h-12 px-8">
                                Member Login
                            </Button>
                        </Link>
                    </div>
                </div>
            </section>

            <!-- Features Grid -->
            <section class="py-20 px-4 bg-card/50">
                <div class="container mx-auto max-w-6xl">
                    <div class="text-center mb-16">
                        <h2 class="text-3xl font-bold mb-4">Why use our Time Tracker?</h2>
                        <p class="text-muted-foreground">Everything you need to account for your offline hours.</p>
                    </div>

                    <div class="grid md:grid-cols-3 gap-8">
                        <!-- Feature 1 -->
                        <div class="bg-background p-6 rounded-xl border shadow-sm hover:shadow-md transition-shadow">
                            <div class="h-12 w-12 bg-primary/10 rounded-lg flex items-center justify-center mb-4 text-primary">
                                <Zap class="h-6 w-6" />
                            </div>
                            <h3 class="text-xl font-semibold mb-2">Instant Logging</h3>
                            <p class="text-muted-foreground">
                                Quickly add time entries with date, duration, and purpose in seconds. Auto-calculated duration helps prevent errors.
                            </p>
                        </div>

                        <!-- Feature 2 -->
                        <div class="bg-background p-6 rounded-xl border shadow-sm hover:shadow-md transition-shadow">
                            <div class="h-12 w-12 bg-blue-500/10 rounded-lg flex items-center justify-center mb-4 text-blue-500">
                                <BarChart3 class="h-6 w-6" />
                            </div>
                            <h3 class="text-xl font-semibold mb-2">Detailed Reports</h3>
                            <p class="text-muted-foreground">
                                View daily breakdowns and monthly summaries. Admins get comprehensive reports for all users.
                            </p>
                        </div>

                        <!-- Feature 3 -->
                        <div class="bg-background p-6 rounded-xl border shadow-sm hover:shadow-md transition-shadow">
                            <div class="h-12 w-12 bg-green-500/10 rounded-lg flex items-center justify-center mb-4 text-green-500">
                                <Shield class="h-6 w-6" />
                            </div>
                            <h3 class="text-xl font-semibold mb-2">Role-Based Access</h3>
                            <p class="text-muted-foreground">
                                Secure environment where users see only their data, while admins manage the big picture.
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Stats Section -->
            <section class="py-20 px-4">
                <div class="container mx-auto max-w-5xl rounded-2xl bg-primary text-primary-foreground p-12 text-center md:text-left flex flex-col md:flex-row items-center justify-between gap-8 shadow-xl">
                    <div>
                        <h2 class="text-3xl font-bold mb-2">Ready to organize your time?</h2>
                        <p class="text-primary-foreground/80">Join the platform and start tracking today.</p>
                    </div>
                    <div class="flex gap-4">
                        <Link href="/register">
                            <Button size="lg" variant="secondary" class="font-semibold">
                                Create Account
                            </Button>
                        </Link>
                    </div>
                </div>
            </section>
        </main>

        <!-- Footer -->
        <footer class="border-t py-12 bg-muted/30">
            <div class="container mx-auto px-4 text-center text-muted-foreground">
                <div class="flex items-center justify-center gap-2 font-bold text-lg text-foreground mb-4">
                    <Clock class="h-5 w-5" />
                    <span>TimeTracker</span>
                </div>
                <p>&copy; {{ new Date().getFullYear() }} Offline Time Tracking System. All rights reserved.</p>
            </div>
        </footer>
    </div>
</template>
