import React from 'react';
import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';

export default function TestSimple() {
    return (
        <AppLayout>
            <Head title="Test Simple" />
            
            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6 text-gray-900">
                            <h1 className="text-4xl font-bold text-green-600 mb-4">
                                ✅ FUNCIONA!
                            </h1>
                            <p className="text-xl">
                                Si ves esto, la navegación funciona perfectamente.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
