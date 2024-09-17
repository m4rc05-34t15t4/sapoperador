import psycopg2
import base64
import pickle
import os
from qgis.core import (QgsVectorLayer, QgsProject, QgsDataSourceUri, QgsLayerTreeLayer, QgsWkbTypes, QgsGeometry, QgsFeature, QgsField, QgsFields, QgsPointXY, QgsVectorDataProvider, QgsProcessingFeatureSourceDefinition, QgsProcessing)
from qgis.utils import iface
from qgis.PyQt.QtCore import QVariant
from qgis.analysis import QgsNativeAlgorithms
#from PyQt5.QtWidgets import QInputDialog

class InputDialog(QDialog):
    def __init__(self):
        super().__init__()
        self.setWindowTitle("Login SAPO")
        
        # Layout
        self.layout = QVBoxLayout()
        
        # ID input
        self.id_label = QLabel("IDT MIL:")
        self.id_input = QLineEdit()
        self.layout.addWidget(self.id_label)
        self.layout.addWidget(self.id_input)
        
        # Password input
        self.password_label = QLabel("Senha:")
        self.password_input = QLineEdit()
        self.password_input.setEchoMode(QLineEdit.Password)
        self.layout.addWidget(self.password_label)
        self.layout.addWidget(self.password_input)
        
        # Submit button
        self.submit_button = QPushButton("Acessar")
        self.submit_button.clicked.connect(self.get_inputs)
        self.layout.addWidget(self.submit_button)
        
        self.setLayout(self.layout)
    
    def get_inputs(self):
        self.idtmil = self.id_input.text()
        self.senha = self.password_input.text()
        
        # Print the inputs or use them in further commands
        #print(f"ID TMIL: {self.idtmil}")
        #print(f"Password: {self.senha}")
        
        # Use the inputs for executing commands
        # Example: self.process_inputs()
        
        self.accept()
    
    # Example function to process inputs
    def process_inputs(self):
        pass
        # Perform some actions with the inputs
        #print(f"Processing ID: {self.idtmil} and Password: {self.senha}")

# Configurações do banco de dados
DB_HOST = "10.46.136.21"
DB_PORT = "5432"
DB_NAME_MOLDURA = "combater_2024_2"
DB_NAME_CLASSES = "combater_2024_2"
DB_USER = "postgres"
DB_PASSWORD = "adminsap"
SCHEMA = "base"

# Função para conectar ao banco de dados
def get_connection(db_name):
    try:
        conn = psycopg2.connect(
            host=DB_HOST,
            port=DB_PORT,
            dbname=db_name,
            user=DB_USER,
            password=DB_PASSWORD
        )
        #print(f"Conexão com o banco de dados {db_name} estabelecida.")
        return conn
    except Exception as e:
        print(f"Erro ao conectar ao banco de dados {db_name}: {e}")
        return None


# Função para obter a geometria da moldura com base no MI
def get_moldura_geom(where):
    conn = get_connection(DB_NAME_MOLDURA)
    if conn is None:
        return None

    try:
        cursor = conn.cursor()
        cursor.execute(f"""
            SELECT ST_AsText(ST_Buffer(ST_Union(geom), 0.0018)), ST_Union(geom)
            FROM public.aux_moldura_a
            WHERE {where}""")
        result = cursor.fetchone()
        cursor.close()
        conn.close()
        if result:
            print(f"Geometria de buffer da moldura obtida com sucesso.")
            return result[0], result[1]  # Retorna a geometria de buffer e a geometria original
        else:
            print(f"Nenhuma geometria encontrada.")
            return None, None
    except Exception as e:
        print(f"Erro ao obter a geometria da moldura: {e}")
        return None, None


# Função para carregar a camada aux_moldura_a com filtro no MI
def load_moldura_layer(filt):
    uri = QgsDataSourceUri()
    uri.setConnection(DB_HOST, DB_PORT, DB_NAME_MOLDURA, DB_USER, DB_PASSWORD)
    uri.setDataSource("public", "aux_moldura_a", "geom", filt)

    layer = QgsVectorLayer(uri.uri(), "aux_moldura_a", "postgres")

    if not layer.isValid():
        print("Falha ao carregar a camada aux_moldura_a.")
        return None

    QgsProject.instance().addMapLayer(layer, False)
    print("Camada aux_moldura_a carregada com sucesso.")
    return layer


# Função para buscar as camadas com feições na área da moldura
def get_layers_with_features(geom_wkt):
    conn = get_connection(DB_NAME_CLASSES)
    if conn is None:
        return []

    try:
        cursor = conn.cursor()
        cursor.execute(f"""
            SELECT table_name
            FROM information_schema.tables
            WHERE table_schema = %s
              AND table_type = 'BASE TABLE'
        """, (SCHEMA,))
        tables = cursor.fetchall()

        valid_tables = []
        for table in tables:
            table_name = table[0]
            if table_name != "aux_moldura_a":  # Ignorar a camada aux_moldura_a
                cursor.execute(f"""
                    SELECT 1
                    FROM {SCHEMA}.{table_name}
                    WHERE ST_Intersects(geom, ST_GeomFromText(%s, 4674))
                    LIMIT 1
                """, (geom_wkt,))
                if cursor.fetchone():
                    valid_tables.append(table_name)

        cursor.close()
        conn.close()
        return valid_tables
    except Exception as e:
        print(f"Erro ao obter camadas com feições: {e}")
        return []
    
# Função para carregar a camada auxiliar com filtro no MI
def load_aux_layer(geom_wkt, main_group):
    uri = QgsDataSourceUri()
    uri.setConnection(DB_HOST, DB_PORT, DB_NAME_MOLDURA, DB_USER, DB_PASSWORD)
    carregadas  = []
    camadas_load = ["aux_obj_ponto_p", "aux_obj_linha_l", "aux_obj_area_a"]
    group = main_group.addGroup("auxiliares")
    for c in camadas_load:
        uri.setDataSource("public", c, "geom")
        layer = QgsVectorLayer(uri.uri(), c, "postgres")
        layer.setSubsetString(f"ST_Intersects(geom, ST_GeomFromText('{geom_wkt}', 4674))")
        if not layer.isValid():
            print("Falha ao carregar a camada "+c)
        else:
            QgsProject.instance().addMapLayer(layer, False)
            group.insertChildNode(-1, QgsLayerTreeLayer(layer))
            #main_group.insertChildNode(-1, QgsLayerTreeLayer(layer))
            carregadas.append(layer)
    if(len(carregadas) == len(camadas_load)):
        print('Camadas auxiliares cartregadas com sucesso!')
    else:
        print('Camadas auxiliares que foram carregadas: '+str(carregadas))
    
    
def carregar_camadas(layers, n_group, main_group, subgroups, g_prefix=False):
    t_geom_group = {"Pontos" : 'points', "Linhas" : 'lines', "Polígonos" : 'polygons'}
    tgg = t_geom_group[n_group]
    list_group = None
    # Carregar as camadas de pontos
    for layer in layers:
        QgsProject.instance().addMapLayer(layer, False)
        group_prefix = layer.name().split('_')[0] if g_prefix else ''

        if list_group is None:
            list_group = main_group.addGroup(n_group)
        if group_prefix not in subgroups[tgg]:
            subgroups[tgg][group_prefix] = list_group.addGroup(group_prefix)

        subgroups[tgg][group_prefix].insertChildNode(-1, QgsLayerTreeLayer(layer))
        layer.setName(layer.name().replace(SCHEMA + ".", ""))
        #print(f"Camada {layer.name()} carregada no grupo '{n_group}', subgrupo '{group_prefix}'.")

def load_classes_layers(geom_wkt, main_group, g_prefix = False):
    subgroups = {
        "points": {},
        "lines": {},
        "polygons": {},
        "auxiliares" : {}
    }

    point_layers = []
    line_layers = []
    polygon_layers = []

    # Conectar ao banco de dados e listar todas as tabelas do esquema
    uri = QgsDataSourceUri()
    uri.setConnection(DB_HOST, DB_PORT, DB_NAME_CLASSES, DB_USER, DB_PASSWORD)
    connection = psycopg2.connect(
        host=DB_HOST,
        port=DB_PORT,
        dbname=DB_NAME_CLASSES,
        user=DB_USER,
        password=DB_PASSWORD
    )
    cursor = connection.cursor()
    cursor.execute(f"""
        SELECT table_name
        FROM information_schema.tables
        WHERE table_schema = '{SCHEMA}'
        AND table_type = 'BASE TABLE';
    """)
    tables = cursor.fetchall()

    # Carregar as camadas
    for table in tables:
        layer_name = table[0]
        uri.setDataSource(SCHEMA, layer_name, "geom")

        layer = QgsVectorLayer(uri.uri(), layer_name, "postgres")

        if not layer.isValid():
            print(f"Falha ao carregar a camada: {layer_name}")
            continue

        # Adicionar o filtro espacial à camada
        layer.setSubsetString(f"ST_Intersects(geom, ST_GeomFromText('{geom_wkt}', 4674))")

        # Verificar o tipo de geometria da camada e adicioná-la à lista apropriada
        if layer.geometryType() == QgsWkbTypes.PointGeometry:
            point_layers.append(layer)
        elif layer.geometryType() == QgsWkbTypes.LineGeometry:
            line_layers.append(layer)
        elif layer.geometryType() == QgsWkbTypes.PolygonGeometry:
            polygon_layers.append(layer)
        else:
            print(f"Tipo de geometria desconhecido para a camada: {layer_name}")

    cursor.close()
    connection.close()

    # Carregar as camadas de pontos
    carregar_camadas(point_layers, "Pontos", main_group, subgroups)
    carregar_camadas(line_layers, "Linhas", main_group, subgroups)
    carregar_camadas(polygon_layers, "Polígonos", main_group, subgroups)

    print("Todas as camadas foram carregadas.")

# Execute a consulta SQL
def obter_nr_funcao(user_id):
    conn = get_connection(DB_NAME_MOLDURA)
    if conn is None:
        return None
    try:
        cursor = conn.cursor()
        cursor.execute("SELECT FUNCAO FROM USUARIOS WHERE USUARIOS.ID = %s;", (user_id,))
    
        # Obtenha o resultado
        result = cursor.fetchone()
    
        # Feche a conexão
        cursor.close()
        conn.close()
        
        # Verifique se um resultado foi encontrado
        if result:
            return int(result[0])
        else:
            return 0
    except Exception as e:
        print(f"Erro ao obter carta em trabalho: {e}")
        return 0
    
def obter_unidade_trabalho(user_id, funcao):
    conn = get_connection(DB_NAME_MOLDURA)
    if conn is None:
        return None
    try:
        fase = ""
        if(funcao == 1):
            fase = "HID"
        elif(funcao == 2):
            fase = "TRA"
        elif(funcao == 4):
            fase = "INT"
        elif(funcao == 8):
            fase = "VEG"
        elif(funcao == 16):
            fase = "REC"

        if(fase == ""):
            print("Fase Não determinada!")
            return
            
        cursor = conn.cursor()
        cursor.execute(f"SELECT ID, MI, MI_25000 FROM aux_moldura_a WHERE OP_{fase} = {user_id} AND NOT DATA_INI_{fase} ISNULL AND DATA_FIN_{fase} ISNULL ORDER BY DATA_INI_{fase} LIMIT 1;")
        result = cursor.fetchone()

        if len(result) > 0:
            cursor.close()
            conn.close()
            return fase, result
        else:
            print(f"""Não há unidade de trabalho na FASE:{fase}.")
            cursor.execute(f"SELECT ID, MI, MI_25000 FROM aux_moldura_a WHERE 
                (OP_HID = {user_id} AND NOT DATA_INI_HID ISNULL AND DATA_FIN_HID ISNULL) OR 
                (OP_TRA = {user_id} AND NOT DATA_INI_TRA ISNULL AND DATA_FIN_TRA ISNULL) OR 
                (OP_INT = {user_id} AND NOT DATA_INI_INT ISNULL AND DATA_FIN_INT ISNULL) OR 
                (OP_VEG = {user_id} AND NOT DATA_INI_VEG ISNULL AND DATA_FIN_VEG ISNULL) OR 
                (OP_REC = {user_id} AND NOT DATA_INI_REC ISNULL AND DATA_FIN_REC ISNULL) 
                ORDER BY DATA_INI_HID, DATA_INI_TRA, DATA_INI_INT, DATA_INI_VEG, DATA_INI_REC LIMIT 1;""")
            result = cursor.fetchone()
            if len(result) > 0:
                print(f"Unidade de Trabalho em outra FASE")
                cursor.close()
                conn.close()
                return "", result
            else:
                print(f"Não há unidade de trabalhoem nenhuma FASE")
                cursor.close()
                conn.close()
                return fase, []
    except Exception as e:
        print(f"Erro ao obter unidade de trabalho: {e}")
        return fase, []
    
def obter_filtro(r, funcao):
    # r[ID, MI, MI_25000]
    if(len(r[1]) > 0):
        filtro = f"id = {r[1][0]}"
        if(funcao == 4):
            filtro = f"mi_25000 = '{r[1][2]}'"
        elif(funcao == 16):
            filtro = f"mi_25000 = '{r[1][2]}'"
            #filtro = f"mi = '{r[1][1]}'"
        return filtro
    else:
        return ""

def obter_area_unidade(user_id, funcao):
    #funcao = obter_nr_funcao(user_id)
    if(funcao > 0 and user_id > 0):
        r = obter_unidade_trabalho(user_id, funcao)
        if(len(r[1]) > 0):
            filtro = obter_filtro(r, funcao)
            geom_wkt, geom = get_moldura_geom(filtro)
            return geom_wkt, geom, filtro, r[0]
        else:
            print("Não existe Unidade de Trabalho")
    return None, None, None, None

def codificar_senha(s):
    # Encode the string
    encoded = base64.b64encode(s.encode())
    # Convert the bytes object to a string
    encoded_str = encoded.decode()
    return encoded_str  # Outputs: SGVsbG8sIHdvcmxkIQ==

def obter_id_usuario(idtmil, senha):
    conn = get_connection(DB_NAME_MOLDURA)
    if conn is None:
        return None
    try:
        cursor = conn.cursor()
        cursor.execute(f"SELECT ID, NOME, FUNCAO, POST_GRAD FROM USUARIOS WHERE IDTMIL = '{idtmil}' AND SENHA = '{senha}';")
        result = cursor.fetchone()
        if len(result) > 0:
            cursor.close()
            conn.close()
            return result
        else:
            print(f"Usuário Não localizado (idtmil: {idtmil}), tente novamente!")
            return []
    except Exception as e:
        print(f"Erro ao obter unidade de trabalho: {e}")
        return []

# Função principal
def main():
    
    #input login
    dialog = InputDialog()
    dialog.exec_()
    idtmil = dialog.idtmil
    senha = dialog.senha

    dados_usu = obter_id_usuario(idtmil, codificar_senha(senha))
    if(len(dados_usu) < 4):
        return
    
    id_usu = dados_usu[0]
    nome_usu = dados_usu[1]
    funcao_usu = dados_usu[2]
    post_grad_usu = dados_usu[3]

    if(not id_usu > 0):
        print("Id usuário inválido!")
        return
    
    print(f"Usuário {post_grad_usu} {nome_usu}, logado com sucesso!")
    
    if not iface:
        print("Interface QGIS (iface) não está disponível.")
        return

    # Solicitar entrada do usuário para o MI
    #mi, ok = QInputDialog.getText(None, "Entrada de MI", "Digite o valor do MI:")
    #if not ok or not mi:
    #    print("MI não fornecido ou cancelado.")
    #    return

    #geom_wkt, geom = get_moldura_geom(f"id = {mi}")
    geom_wkt, geom, filtro, fase = obter_area_unidade(id_usu, funcao_usu)
    if not geom_wkt:
        print("Não foi possível obter a geometria da moldura.")
        return

    # Definir a área de trabalho do QGIS como a área do buffer
    geom = QgsGeometry.fromWkt(geom_wkt)
    iface.mapCanvas().setExtent(geom.boundingBox())

    # Criar grupo principal com o nome do MI
    root = QgsProject.instance().layerTreeRoot()
    main_group = root.addGroup(f"{nome_usu}_{fase}_{filtro}")

    #carrega camadas auxiliares
    load_aux_layer(geom_wkt, main_group)

    # Carregar as camadas do banco de dados pit_topo_pe_2024, esquema edgv
    load_classes_layers(geom_wkt, main_group)

    # Carregar a camada aux_moldura_a com filtro no MI e adicionar ao grupo principal
    moldura_layer = load_moldura_layer(filtro)
    if not moldura_layer:
        print("Falha ao carregar a camada aux_moldura_a.")
        return
    main_group.insertChildNode(-1, QgsLayerTreeLayer(moldura_layer))
    print("Moldura adicionada ao grupo principal.")

# Executar a função principal
main()
