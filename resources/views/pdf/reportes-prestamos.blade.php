<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Préstamos</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 3px solid #2a3d83; }
        .header h1 { color: #2a3d83; font-size: 24px; margin-bottom: 10px; }
        .info-box { background-color: #f8f9fa; padding: 15px; margin-bottom: 20px; border-left: 4px solid #2a3d83; }
        .section-title { background-color: #2a3d83; color: white; padding: 10px; margin-top: 25px; margin-bottom: 15px; font-weight: bold; }
        .stats-grid { width: 100%; margin-bottom: 20px; }
        .stat-card { display: inline-block; width: 18%; padding: 15px; text-align: center; background-color: #f8f9fa; border: 1px solid #dee2e6; margin: 5px; }
        .stat-card .number { font-size: 28px; font-weight: bold; color: #2a3d83; }
        .stat-card .label { font-size: 11px; color: #666; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        table thead { background-color: #2a3d83; color: white; }
        table th { padding: 10px; text-align: left; font-size: 11px; }
        table td { padding: 8px 10px; border-bottom: 1px solid #dee2e6; font-size: 11px; }
        table tbody tr:nth-child(even) { background-color: #f8f9fa; }
        .footer { text-align: center; font-size: 10px; color: #666; margin-top: 30px; padding-top: 10px; border-top: 1px solid #dee2e6; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Reporte de Préstamos y Consultas</h1>
        <p>Sistema de Gestión Documental y Archivos</p>
    </div>

    <div class="info-box">
        <p><strong>Fecha de Generación:</strong> {{ $fechaGeneracion }}</p>
        <p><strong>Generado por:</strong> {{ $usuario }}</p>
    </div>

    <div class="section-title">ESTADÍSTICAS GENERALES</div>
    
    <div class="stats-grid">
        <div class="stat-card">
            <span class="number">{{ $estadisticas['total'] }}</span>
            <span class="label">Total Préstamos</span>
        </div>
        <div class="stat-card">
            <span class="number">{{ $estadisticas['activos'] }}</span>
            <span class="label">Activos</span>
        </div>
        <div class="stat-card">
            <span class="number">{{ $estadisticas['vencidos'] }}</span>
            <span class="label">Vencidos</span>
        </div>
        <div class="stat-card">
            <span class="number">{{ $estadisticas['devueltos'] }}</span>
            <span class="label">Devueltos</span>
        </div>
        <div class="stat-card">
            <span class="number">{{ $estadisticas['proximos_vencer'] }}</span>
            <span class="label">Por Vencer</span>
        </div>
    </div>

    <div class="section-title">PRÉSTAMOS POR TIPO</div>
    <table>
        <thead>
            <tr>
                <th>Tipo</th>
                <th>Cantidad</th>
                <th>Porcentaje</th>
            </tr>
        </thead>
        <tbody>
            @foreach($prestamosPorTipo as $tipo => $total)
            <tr>
                <td style="text-transform: capitalize;">{{ $tipo }}</td>
                <td>{{ $total }}</td>
                <td>{{ $estadisticas['total'] > 0 ? number_format(($total / $estadisticas['total']) * 100, 1) : 0 }}%</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="section-title">PRÉSTAMOS POR ESTADO</div>
    <table>
        <thead>
            <tr>
                <th>Estado</th>
                <th>Cantidad</th>
                <th>Porcentaje</th>
            </tr>
        </thead>
        <tbody>
            @foreach($prestamosPorEstado as $estado => $total)
            <tr>
                <td style="text-transform: capitalize;">{{ $estado }}</td>
                <td>{{ $total }}</td>
                <td>{{ $estadisticas['total'] > 0 ? number_format(($total / $estadisticas['total']) * 100, 1) : 0 }}%</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="section-title">TIEMPO PROMEDIO DE PRÉSTAMO</div>
    <div class="info-box" style="text-align: center; font-size: 18px;">
        <strong style="font-size: 36px; color: #2a3d83;">{{ $tiempoPromedio }}</strong> días promedio
    </div>

    @if(count($expedientesMasPrestados) > 0)
    <div class="section-title">TOP 10 EXPEDIENTES MÁS PRESTADOS</div>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Expediente</th>
                <th>Total Préstamos</th>
            </tr>
        </thead>
        <tbody>
            @foreach($expedientesMasPrestados as $index => $item)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $item['expediente'] }}</td>
                <td>{{ $item['total'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    @if(count($usuariosMasSolicitan) > 0)
    <div class="section-title">TOP 10 USUARIOS MÁS ACTIVOS</div>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Usuario</th>
                <th>Email</th>
                <th>Total Préstamos</th>
            </tr>
        </thead>
        <tbody>
            @foreach($usuariosMasSolicitan as $index => $item)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $item['usuario'] }}</td>
                <td>{{ $item['email'] }}</td>
                <td>{{ $item['total'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    @if(count($prestamosVencidos) > 0)
    <div style="page-break-before: always;"></div>
    <div class="section-title" style="background-color: #dc3545;">PRÉSTAMOS VENCIDOS - REQUIEREN ATENCIÓN</div>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Item</th>
                <th>Solicitante</th>
                <th>Fecha Préstamo</th>
                <th>Debía Devolver</th>
                <th>Días Vencido</th>
            </tr>
        </thead>
        <tbody>
            @foreach($prestamosVencidos as $prestamo)
            <tr>
                <td>#{{ $prestamo['id'] }}</td>
                <td>{{ $prestamo['item'] }}</td>
                <td>{{ $prestamo['solicitante'] }}</td>
                <td>{{ \Carbon\Carbon::parse($prestamo['fecha_prestamo'])->format('d/m/Y') }}</td>
                <td>{{ \Carbon\Carbon::parse($prestamo['fecha_devolucion_esperada'])->format('d/m/Y') }}</td>
                <td style="color: #dc3545; font-weight: bold;">{{ $prestamo['dias_vencido'] }} días</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <div class="footer">
        <p>Sistema de Gestión Documental y Archivos - Reporte generado el {{ $fechaGeneracion }}</p>
        <p>Este documento es confidencial y solo debe ser usado para fines autorizados</p>
    </div>
</body>
</html>
