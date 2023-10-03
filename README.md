# Marshaller bench

Compares `json_encode`/`json_decode`, Symfony Serializer component and Symfony JsonMarshaller component in terms of performance and memory usage.

## Results

### Serialize

```
+----------------------------+----------+-----------+--------+-----------+
| subject                    | memory   | mode      | rstdev | stdev     |
+----------------------------+----------+-----------+--------+-----------+
| bench (json_encode)        | 7.828mb  | 5.666ms   | ±1.19% | 67.468μs  |
| bench (Marshaller (eager)) | 7.828mb  | 6.619ms   | ±1.52% | 101.051μs |
| bench (Marshaller (lazy))  | 7.828mb  | 6.171ms   | ±1.27% | 78.667μs  |
| bench (Serializer (light)) | 15.943mb | 264.483ms | ±0.96% | 2.536ms   |
| bench (Serializer (heavy)) | 16.059mb | 465.069ms | ±1.10% | 5.107ms   |
+----------------------------+----------+-----------+--------+-----------+
```

### Deserialize

```
+------------------------------+-----------+-----------+--------+----------+
| subject                      | memory    | mode      | rstdev | stdev    |
+------------------------------+-----------+-----------+--------+----------+
| bench (json_decode)          | 85.723mb  | 87.972ms  | ±1.30% | 1.151ms  |
| bench (Unmarshaller (eager)) | 75.469mb  | 150.811ms | ±0.76% | 1.154ms  |
| bench (Unmarshaller (lazy))  | 9.314mb   | 493.498ms | ±1.46% | 7.279ms  |
| bench (Serializer (light))   | 69.685mb  | 914.028ms | ±1.52% | 14.004ms |
| bench (Serializer (heavy))   | 156.949mb | 4.772s    | ±1.67% | 79.652ms |
+------------------------------+-----------+-----------+--------+----------+
```
