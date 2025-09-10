import { ImgHTMLAttributes } from 'react';

export default function AppLogoIcon(props: ImgHTMLAttributes<HTMLImageElement>) {
    return (
        <img 
            {...props}
            src="/images/Logo2.PNG"
            alt="Archivey Cloud SGDEA"
            className={`w-8 h-8 object-contain ${props.className || ''}`}
        />
    );
}
