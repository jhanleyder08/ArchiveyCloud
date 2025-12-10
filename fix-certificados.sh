#!/bin/bash

echo "🔧 Arreglando problema de certificados..."

# 1. Verificar el archivo TypeScript actual
echo "📝 Verificando archivo TypeScript..."
if grep -q "const stats = estadisticas" resources/js/pages/admin/firmas/certificados.tsx; then
    echo "✅ El componente ya tiene la protección stats"
else
    echo "❌ Falta la protección. Agregándola..."
    # Hacer backup
    cp resources/js/pages/admin/firmas/certificados.tsx resources/js/pages/admin/firmas/certificados.tsx.backup
    
    # Agregar la protección después de la línea de export default
    sed -i '/export default function CertificadosIndex/a\    \/\/ Valores por defecto para estadisticas si es undefined\n    const stats = estadisticas || {\n        total: 0,\n        activos: 0,\n        proximos_vencer: 0,\n        vencidos: 0,\n        revocados: 0\n    };' resources/js/pages/admin/firmas/certificados.tsx
    
    # Reemplazar todas las referencias
    sed -i 's/estadisticas\.total/stats.total/g' resources/js/pages/admin/firmas/certificados.tsx
    sed -i 's/estadisticas\.activos/stats.activos/g' resources/js/pages/admin/firmas/certificados.tsx
    sed -i 's/estadisticas\.proximos_vencer/stats.proximos_vencer/g' resources/js/pages/admin/firmas/certificados.tsx
    sed -i 's/estadisticas\.vencidos/stats.vencidos/g' resources/js/pages/admin/firmas/certificados.tsx
    sed -i 's/estadisticas\.revocados/stats.revocados/g' resources/js/pages/admin/firmas/certificados.tsx
fi

# 2. Limpiar cachés de Laravel
echo "🧹 Limpiando cachés de Laravel..."
php artisan optimize:clear > /dev/null 2>&1

# 3. Eliminar build antiguo
echo "🗑️  Eliminando build antiguo..."
rm -rf public/build/*

# 4. Reconstruir frontend
echo "🏗️  Reconstruyendo frontend (esto toma ~30s)..."
npm run build > /dev/null 2>&1

if [ $? -eq 0 ]; then
    echo "✅ Build completado exitosamente"
    
    # Obtener el nuevo hash del archivo
    NEW_HASH=$(grep -A 1 "admin/firmas/certificados.tsx" public/build/manifest.json | grep "file" | sed 's/.*certificados-\(.*\)\.js.*/\1/')
    echo "📦 Nuevo archivo: certificados-${NEW_HASH}.js"
else
    echo "❌ Error en el build"
    exit 1
fi

# 5. Limpiar cachés nuevamente
echo "🧹 Limpiando cachés finales..."
php artisan optimize:clear > /dev/null 2>&1

echo ""
echo "✨ ¡ARREGLO COMPLETADO!"
echo ""
echo "🔍 Ahora haz lo siguiente:"
echo "   1. Abre el navegador en modo INCÓGNITO (Ctrl+Shift+N)"
echo "   2. Ve a: http://127.0.0.1:8000/admin/firmas/certificados"
echo "   3. Deberías ver la página sin errores (con valores en 0)"
echo ""
echo "💡 Si aún ves el error:"
echo "   - Presiona F12 → Pestaña 'Network' → Marca 'Disable cache'"
echo "   - Recarga con Ctrl+Shift+R"
echo ""
