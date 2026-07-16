import sys
import pandas as pd
import json
import warnings
warnings.filterwarnings('ignore')

try:
    file_path = sys.argv[1]
    
    # Intentar leer como Excel (XLSX o XLS)
    try:
        df = pd.read_excel(file_path)
    except Exception as e:
        # Si falla, a veces los sistemas exportan HTML disfrazado de XLS
        try:
            dfs = pd.read_html(file_path)
            if len(dfs) > 0:
                df = dfs[0]
            else:
                raise Exception("No se encontraron tablas HTML")
        except Exception as e2:
            # Si falla, intentar como CSV
            try:
                df = pd.read_csv(file_path, sep=None, engine='python')
            except Exception as e3:
                print(json.dumps({'error': "Error reading as Excel: " + str(e) + " | HTML: " + str(e2) + " | CSV: " + str(e3)}))
                sys.exit(0)

    # Replace NaN with empty string to ensure valid JSON output
    df = df.fillna('')
    # Convert to list of dicts
    records = df.to_dict('records')
    print(json.dumps(records))
except Exception as e:
    print(json.dumps({'error': str(e)}))
