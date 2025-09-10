import { ImgHTMLAttributes } from 'react';

export default function AppLogoIcon(props: ImgHTMLAttributes<HTMLImageElement>) {
    return (
        <div className="flex items-center justify-center gap-4">
            <img 
                src="/images/logo.png"
                alt="Archivey Cloud"
                className="w-32 h-32 object-contain"
            />
            <img 
                src="/images/Logo2.PNG"
                alt="Archivey Cloud SGDEA"
                className="w-32 h-32 object-contain"
            />
        </div>
    );
}
