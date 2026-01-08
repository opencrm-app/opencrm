<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { FileText, Twitter, Github } from 'lucide-vue-next';
</script>

<template>
    <footer class="bg-white border-t border-gray-100 dark:bg-zinc-950 dark:border-zinc-800">
        <div class="mx-auto max-w-7xl px-6 py-12 lg:px-8">
            <div class="grid grid-cols-1 gap-8 lg:grid-cols-4">
                <div class="lg:col-span-1">
                    <div class="flex items-center gap-3">
                        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-primary">
                            <FileText class="h-5 w-5 text-primary-foreground" />
                        </div>
                        <div class="font-bold text-xl">
                            <span class="text-primary">Invoice</span>Maker
                        </div>
                    </div>
                    <p class="mt-4 text-sm leading-6 text-gray-600 dark:text-gray-400">
                        Create professional invoices for free. No signup required. The fastest way to bill your clients.
                    </p>
                    <div class="mt-6 flex space-x-4">
                        <a href="#" class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">
                            <span class="sr-only">Twitter</span>
                            <Twitter class="h-5 w-5" />
                        </a>
                        <a href="#" class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">
                            <span class="sr-only">GitHub</span>
                            <Github class="h-5 w-5" />
                        </a>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-8 lg:col-span-3 lg:grid-cols-3">
                    <div>
                        <h3 class="text-sm font-semibold leading-6 text-gray-900 dark:text-white">Product</h3>
                        <ul role="list" class="mt-6 space-y-4">
                            <li>
                                <Link href="/invoice-builder" class="text-sm leading-6 text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">Create Invoice</Link>
                            </li>
                            <li>
                                <a href="/#features" class="text-sm leading-6 text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">Features</a>
                            </li>
                            <li>
                                <a href="/#templates" class="text-sm leading-6 text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">Templates</a>
                            </li>
                            <li>
                                <a href="/#how-it-works" class="text-sm leading-6 text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">How It Works</a>
                            </li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold leading-6 text-gray-900 dark:text-white">Resources</h3>
                        <ul role="list" class="mt-6 space-y-4">
                            <li>
                                <Link href="/invoice-builder" class="text-sm leading-6 text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">Invoice Generator</Link>
                            </li>
                            <li>
                                <Link href="/invoice-builder" class="text-sm leading-6 text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">Invoice Templates</Link>
                            </li>
                            <li>
                                <Link href="/invoice-builder" class="text-sm leading-6 text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">PDF Invoice Maker</Link>
                            </li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold leading-6 text-gray-900 dark:text-white">Legal</h3>
                        <ul role="list" class="mt-6 space-y-4">
                            <li>
                                <a href="#" class="text-sm leading-6 text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">Privacy Policy</a>
                            </li>
                            <li>
                                <a href="#" class="text-sm leading-6 text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">Terms of Service</a>
                            </li>
                            <li>
                                <a href="#" class="text-sm leading-6 text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">Contact Us</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="mt-12 border-t border-gray-900/10 pt-8 dark:border-white/10">
                <p class="text-xs leading-5 text-gray-500 dark:text-gray-400">&copy; {{ new Date().getFullYear() }} Free Online Invoice Maker. All rights reserved. Create unlimited invoices for free.</p>
            </div>
        </div>
    </footer>
</template>
