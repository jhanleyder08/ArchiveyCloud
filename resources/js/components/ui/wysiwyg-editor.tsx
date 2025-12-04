import React, { useState } from 'react';
import { Textarea } from './textarea';
import { Button } from './button';
import { Badge } from './badge';
import { Card, CardContent, CardHeader, CardTitle } from './card';
import { 
    Bold, 
    Italic, 
    Underline, 
    List, 
    ListOrdered, 
    AlignLeft, 
    AlignCenter, 
    AlignRight,
    Code,
    Eye,
    Type,
    Plus
} from 'lucide-react';

interface WysiwygEditorProps {
    value: string;
    onChange: (content: string) => void;
    height?: number;
    placeholder?: string;
    readonly?: boolean;
}

export function WysiwygEditor({
    value,
    onChange,
    height = 400,
    placeholder = "Escriba aquí el contenido de su plantilla...",
    readonly = false
}: WysiwygEditorProps) {
    const [viewMode, setViewMode] = useState<'edit' | 'preview'>('edit');
    const [variables] = useState([
        'nombre_destinatario', 'fecha', 'numero_documento', 'asunto',
        'remitente', 'cargo_remitente', 'empresa', 'ciudad'
    ]);

    const insertVariable = (variable: string) => {
        const newContent = value + `{{${variable}}}`;
        onChange(newContent);
    };

    const applyFormat = (format: string) => {
        // Implementación básica de formato
        const textarea = document.querySelector('textarea') as HTMLTextAreaElement;
        if (textarea) {
            const start = textarea.selectionStart;
            const end = textarea.selectionEnd;
            const selectedText = value.substring(start, end);
            
            let formattedText = '';
            switch (format) {
                case 'bold':
                    formattedText = `<strong>${selectedText}</strong>`;
                    break;
                case 'italic':
                    formattedText = `<em>${selectedText}</em>`;
                    break;
                case 'underline':
                    formattedText = `<u>${selectedText}</u>`;
                    break;
                case 'h1':
                    formattedText = `<h1>${selectedText}</h1>`;
                    break;
                case 'h2':
                    formattedText = `<h2>${selectedText}</h2>`;
                    break;
                case 'ul':
                    formattedText = `<ul><li>${selectedText}</li></ul>`;
                    break;
                case 'ol':
                    formattedText = `<ol><li>${selectedText}</li></ol>`;
                    break;
                default:
                    formattedText = selectedText;
            }
            
            const newContent = value.substring(0, start) + formattedText + value.substring(end);
            onChange(newContent);
        }
    };

    const renderPreview = () => {
        return (
            <div 
                className="prose prose-sm max-w-none p-4 border rounded-md min-h-[400px] bg-white"
                dangerouslySetInnerHTML={{ __html: value }}
            />
        );
    };

    return (
        <div className="w-full space-y-4">
            {/* Toolbar */}
            <Card>
                <CardHeader className="pb-3">
                    <div className="flex items-center justify-between">
                        <CardTitle className="text-sm">Editor de Plantillas</CardTitle>
                        <div className="flex items-center space-x-2">
                            <Button
                                variant={viewMode === 'edit' ? 'default' : 'outline'}
                                size="sm"
                                onClick={() => setViewMode('edit')}
                            >
                                <Code className="h-4 w-4 mr-1" />
                                Editar
                            </Button>
                            <Button
                                variant={viewMode === 'preview' ? 'default' : 'outline'}
                                size="sm"
                                onClick={() => setViewMode('preview')}
                            >
                                <Eye className="h-4 w-4 mr-1" />
                                Vista Previa
                            </Button>
                        </div>
                    </div>
                </CardHeader>
                <CardContent className="space-y-3">
                    {/* Botones de Formato */}
                    <div className="flex flex-wrap gap-1">
                        <Button variant="outline" size="sm" onClick={() => applyFormat('bold')}>
                            <Bold className="h-4 w-4" />
                        </Button>
                        <Button variant="outline" size="sm" onClick={() => applyFormat('italic')}>
                            <Italic className="h-4 w-4" />
                        </Button>
                        <Button variant="outline" size="sm" onClick={() => applyFormat('underline')}>
                            <Underline className="h-4 w-4" />
                        </Button>
                        
                        <div className="h-6 w-px bg-gray-300 mx-1" />
                        
                        <Button variant="outline" size="sm" onClick={() => applyFormat('h1')}>
                            <Type className="h-4 w-4" />
                            <span className="ml-1 text-xs">H1</span>
                        </Button>
                        <Button variant="outline" size="sm" onClick={() => applyFormat('h2')}>
                            <Type className="h-4 w-4" />
                            <span className="ml-1 text-xs">H2</span>
                        </Button>
                        
                        <div className="h-6 w-px bg-gray-300 mx-1" />
                        
                        <Button variant="outline" size="sm" onClick={() => applyFormat('ul')}>
                            <List className="h-4 w-4" />
                        </Button>
                        <Button variant="outline" size="sm" onClick={() => applyFormat('ol')}>
                            <ListOrdered className="h-4 w-4" />
                        </Button>
                        
                        <div className="h-6 w-px bg-gray-300 mx-1" />
                        
                        <Button variant="outline" size="sm" onClick={() => applyFormat('left')}>
                            <AlignLeft className="h-4 w-4" />
                        </Button>
                        <Button variant="outline" size="sm" onClick={() => applyFormat('center')}>
                            <AlignCenter className="h-4 w-4" />
                        </Button>
                        <Button variant="outline" size="sm" onClick={() => applyFormat('right')}>
                            <AlignRight className="h-4 w-4" />
                        </Button>
                    </div>
                    
                    {/* Variables Disponibles */}
                    <div>
                        <p className="text-sm font-medium mb-2">Variables Disponibles:</p>
                        <div className="flex flex-wrap gap-1">
                            {variables.map((variable) => (
                                <Button
                                    key={variable}
                                    variant="outline"
                                    size="sm"
                                    onClick={() => insertVariable(variable)}
                                    className="text-xs"
                                >
                                    <Plus className="h-3 w-3 mr-1" />
                                    {variable}
                                </Button>
                            ))}
                        </div>
                    </div>
                </CardContent>
            </Card>

            {/* Editor/Preview Area */}
            <div className="border rounded-md">
                {viewMode === 'edit' ? (
                    <Textarea
                        value={value}
                        onChange={(e) => onChange(e.target.value)}
                        placeholder={placeholder}
                        disabled={readonly}
                        className="resize-none border-0 focus:ring-0"
                        style={{ height: `${height}px` }}
                    />
                ) : (
                    renderPreview()
                )}
            </div>
            
            {/* Plantillas Predefinidas */}
            <Card>
                <CardHeader className="pb-3">
                    <CardTitle className="text-sm">Plantillas Predefinidas</CardTitle>
                </CardHeader>
                <CardContent>
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-2">
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => onChange(`
<div style="margin: 20px;">
    <div style="text-align: right; margin-bottom: 30px;">
        {{ciudad}}, {{fecha}}
    </div>
    
    <div style="margin-bottom: 20px;">
        <strong>Señor(a):</strong><br>
        {{nombre_destinatario}}<br>
        {{cargo_destinatario}}<br>
        {{empresa_destinatario}}
    </div>
    
    <div style="margin-bottom: 20px;">
        <strong>Asunto:</strong> {{asunto}}
    </div>
    
    <div style="margin-bottom: 20px;">
        <p>Estimado(a) {{nombre_destinatario}},</p>
        
        <p>{{contenido_principal}}</p>
        
        <p>Sin otro particular, me despido cordialmente.</p>
    </div>
    
    <div style="margin-top: 40px;">
        <p>Atentamente,</p><br>
        <p><strong>{{firmante}}</strong><br>
        {{cargo_firmante}}<br>
        {{empresa}}</p>
    </div>
</div>
                            `)}
                        >
                            Carta Formal
                        </Button>
                        
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => onChange(`
<div style="margin: 20px;">
    <div style="text-align: center; margin-bottom: 30px;">
        <h2><strong>MEMORANDO</strong></h2>
        <p>No. {{numero_memorando}}</p>
    </div>
    
    <table style="width: 100%; margin-bottom: 20px;">
        <tr>
            <td><strong>PARA:</strong></td>
            <td>{{destinatario}}</td>
        </tr>
        <tr>
            <td><strong>DE:</strong></td>
            <td>{{remitente}}</td>
        </tr>
        <tr>
            <td><strong>FECHA:</strong></td>
            <td>{{fecha}}</td>
        </tr>
        <tr>
            <td><strong>ASUNTO:</strong></td>
            <td>{{asunto}}</td>
        </tr>
    </table>
    
    <div style="text-align: justify; line-height: 1.6;">
        {{contenido}}
    </div>
</div>
                            `)}
                        >
                            Memorando
                        </Button>
                        
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => onChange(`
<div style="margin: 20px;">
    <div style="text-align: center; margin-bottom: 30px;">
        <h1><strong>CERTIFICACIÓN</strong></h1>
    </div>
    
    <p>El suscrito {{cargo_certificador}}, certifica que:</p>
    
    <div style="margin: 20px 0; text-align: justify;">
        {{contenido_certificacion}}
    </div>
    
    <p>Se expide la presente certificación en {{ciudad}}, a los {{fecha}}.</p>
    
    <div style="margin-top: 60px; text-align: center;">
        <p><strong>{{firmante}}</strong><br>
        {{cargo_firmante}}</p>
    </div>
</div>
                            `)}
                        >
                            Certificación
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </div>
    );
}
