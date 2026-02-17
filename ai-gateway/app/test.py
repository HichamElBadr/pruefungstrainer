numbers = [5, 2, 10, 4, 8]
temp = numbers[0]

for j in range(len(numbers)-1):   #for(int i; i < numbers.length(); i++)
    for i in range(len(numbers)-1):
        if numbers[i] > numbers[i+1]:
            temp = numbers[i+1]
            numbers[i+1] = numbers[i]
            numbers[i] = temp

print(numbers)
    