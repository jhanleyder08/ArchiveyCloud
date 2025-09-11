import { useState } from 'react';
import { HelpCircle, Mail, User, MessageSquare, Send, X } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from '@/components/ui/dialog';
import { router } from '@inertiajs/react';

interface SupportModalProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
}

export default function SupportModal({ open, onOpenChange }: SupportModalProps) {
    const [formData, setFormData] = useState({
        name: '',
        email: '',
        subject: 'Soporte Técnico',
        message: ''
    });
    const [isSubmitting, setIsSubmitting] = useState(false);

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setIsSubmitting(true);

        router.post('/support', formData, {
            onSuccess: () => {
                // Reset form
                setFormData({
                    name: '',
                    email: '',
                    subject: 'Soporte Técnico',
                    message: ''
                });
                // Close modal
                onOpenChange(false);
            },
            onError: (errors) => {
                console.error('Error al enviar ticket:', errors);
            },
            onFinish: () => {
                setIsSubmitting(false);
            }
        });
    };

    const handleClose = () => {
        // Reset form when closing
        setFormData({
            name: '',
            email: '',
            subject: 'Soporte Técnico',
            message: ''
        });
        onOpenChange(false);
    };

    return (
        <Dialog open={open} onOpenChange={handleClose}>
            <DialogContent className="sm:max-w-[500px] max-h-[90vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle className="flex items-center gap-2 text-xl">
                        <HelpCircle className="h-5 w-5 text-[#2a3d83]" />
                        Soporte Técnico
                    </DialogTitle>
                    <DialogDescription>
                        Complete el formulario para contactar nuestro equipo de soporte técnico. 
                        Nos pondremos en contacto con usted lo antes posible.
                    </DialogDescription>
                </DialogHeader>

                <form onSubmit={handleSubmit} className="space-y-4 mt-4">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div className="space-y-2">
                            <Label htmlFor="support-name" className="flex items-center gap-2 text-sm font-medium text-gray-700">
                                <User className="h-4 w-4" />
                                Nombre *
                            </Label>
                            <Input
                                id="support-name"
                                name="name"
                                type="text"
                                value={formData.name}
                                onChange={(e: React.ChangeEvent<HTMLInputElement>) => 
                                    setFormData({...formData, name: e.target.value})
                                }
                                placeholder="Tu nombre completo"
                                required
                                disabled={isSubmitting}
                                className="w-full"
                            />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="support-email" className="flex items-center gap-2 text-sm font-medium text-gray-700">
                                <Mail className="h-4 w-4" />
                                Email *
                            </Label>
                            <Input
                                id="support-email"
                                name="email"
                                type="email"
                                value={formData.email}
                                onChange={(e: React.ChangeEvent<HTMLInputElement>) => 
                                    setFormData({...formData, email: e.target.value})
                                }
                                placeholder="tu@email.com"
                                required
                                disabled={isSubmitting}
                                className="w-full"
                            />
                        </div>
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="support-subject" className="text-sm font-medium text-gray-700">
                            Asunto *
                        </Label>
                        <Input
                            id="support-subject"
                            name="subject"
                            type="text"
                            value={formData.subject}
                            onChange={(e: React.ChangeEvent<HTMLInputElement>) => 
                                setFormData({...formData, subject: e.target.value})
                            }
                            placeholder="Soporte Técnico"
                            required
                            disabled={isSubmitting}
                            className="w-full"
                        />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="support-message" className="flex items-center gap-2 text-sm font-medium text-gray-700">
                            <MessageSquare className="h-4 w-4" />
                            Mensaje *
                        </Label>
                        <Textarea
                            id="support-message"
                            name="message"
                            value={formData.message}
                            onChange={(e: React.ChangeEvent<HTMLTextAreaElement>) => 
                                setFormData({...formData, message: e.target.value})
                            }
                            placeholder="Describe tu problema o consulta en detalle..."
                            required
                            disabled={isSubmitting}
                            rows={4}
                            className="w-full resize-none"
                        />
                    </div>

                    {/* Info box */}
                    <div className="bg-blue-50 border border-blue-200 rounded-lg p-3">
                        <div className="flex items-start gap-2">
                            <HelpCircle className="h-4 w-4 text-blue-600 mt-0.5 flex-shrink-0" />
                            <div>
                                <p className="text-sm text-blue-700">
                                    <strong>Tiempo de respuesta:</strong> Nuestro equipo se esfuerza por responder 
                                    dentro de las primeras 24 horas. Para urgencias, incluye "URGENTE" en el asunto.
                                </p>
                            </div>
                        </div>
                    </div>

                    {/* Buttons */}
                    <div className="flex justify-end gap-3 pt-4 border-t">
                        <Button 
                            type="button" 
                            variant="outline" 
                            onClick={handleClose}
                            disabled={isSubmitting}
                        >
                            Cancelar
                        </Button>
                        <Button 
                            type="submit" 
                            disabled={isSubmitting}
                            className="bg-[#2a3d83] hover:bg-[#1e2b5f] text-white"
                        >
                            {isSubmitting ? (
                                <>
                                    <div className="animate-spin h-4 w-4 mr-2 border-2 border-white border-t-transparent rounded-full"></div>
                                    Enviando...
                                </>
                            ) : (
                                <>
                                    <Send className="h-4 w-4 mr-2" />
                                    Enviar Ticket
                                </>
                            )}
                        </Button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>
    );
}
