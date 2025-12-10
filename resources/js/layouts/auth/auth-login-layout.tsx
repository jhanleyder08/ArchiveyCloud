import BrandingProvider from '@/components/BrandingProvider';
import Galaxy from '@/components/ui/Galaxy';

interface AuthLoginLayoutProps {
    children: React.ReactNode;
}

export default function AuthLoginLayout({ children }: AuthLoginLayoutProps) {
    return (
        <>
            <BrandingProvider />
            <div className="relative flex min-h-svh items-center justify-center bg-white p-4 md:p-6 overflow-hidden">
                {/* Galaxy Background Effect - Full page */}
                <div className="opacity-30">
                    <Galaxy 
                        hueShift={220}
                        density={0.4}
                        glowIntensity={0.1}
                        saturation={0.2}
                        speed={0.2}
                        rotationSpeed={0.01}
                        twinkleIntensity={0.3}
                        mouseInteraction={false}
                        mouseRepulsion={false}
                        transparent={true}
                    />
                </div>
                
                <div className="relative z-10 w-full max-w-5xl">
                    {/* Login Form Container */}
                    <div className="flex flex-col md:flex-row overflow-hidden shadow-2xl rounded-xl backdrop-blur-sm">
                        {/* Left Section - White Background (2/3 width) */}
                        <div className="flex-[2] bg-white/95 p-8 md:p-10 lg:p-12">
                            {children}
                        </div>

                        {/* Right Section - Azul Institucional (1/3 width) */}
                        <div className="relative flex-[1] flex items-center justify-center p-6 md:p-8 min-h-[300px] md:min-h-[500px] bg-[#2a3d83]">
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
        </>
    );
}

