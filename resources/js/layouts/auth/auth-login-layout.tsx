import { type PropsWithChildren } from 'react';

interface AuthLoginLayoutProps {
    children: React.ReactNode;
}

export default function AuthLoginLayout({ children }: AuthLoginLayoutProps) {
    return (
        <div className="flex min-h-svh items-center justify-center bg-white p-4 md:p-6">
            <div className="relative w-full max-w-5xl">
                {/* Login Form Container */}
                <div className="flex flex-col md:flex-row overflow-hidden shadow-2xl">
                    {/* Left Section - White Background (2/3 width) */}
                    <div className="flex-[2] bg-white p-8 md:p-10 lg:p-12">
                        {children}
                    </div>

                    {/* Right Section - Azul Institucional Background (1/3 width) */}
                    <div className="relative flex-[1] bg-[#2a3d83] flex items-center justify-center p-6 md:p-8 min-h-[300px] md:min-h-[500px]">
                        {/* Logo */}
                        <div className="flex items-center justify-center w-full">
                            <img 
                                src="/Logo2.svg" 
                                alt="Archivey Cloud SGDEA" 
                                className="w-40 h-40 md:w-64 md:h-64 lg:w-72 lg:h-72 object-contain"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}

